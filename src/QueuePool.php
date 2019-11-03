<?php declare(strict_types=1);


namespace Swoft\Queue;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Co;
use Swoft\Exception\SwoftException;
use Swoft\Log\Helper\CLog;
use Swoft\Queue\Annotation\Mapping\Job;
use Swoft\Queue\Exception\ProcessException;
use Swoft\Queue\Manager\QueueManager;
use Swoft\Server\Helper\ServerHelper;
use Swoft\Stdlib\Helper\Dir;
use Swoft\Stdlib\Helper\Sys;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Process\Pool;

/**
 * Class ProcessPool
 *
 * @since 2.0
 *
 * @Bean(name="queueServer")
 */
class QueuePool
{
    /**
     * @var QueuePool
     */
    public static $processPool;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var int
     */
    private $workerNum = 0;

    /**
     * @var int
     */
    private $ipcType = SWOOLE_IPC_NONE;

    /**
     * @var int
     */
    private $msgQueueKey = 0;

    /**
     * @var bool
     */
    private $coroutine = true;

    /**
     * @var array
     * @example
     * [
     *     'eventName' => xxxListener
     * ]
     */
    private $on = [];

    /**
     * @var string
     */
    private $pidFile = '@runtime/swoft-queue.pid';

    /**
     * @var string
     */
    private $pidName = 'swoft-queue';

    /**
     * @var string
     */
    private $scriptFile = '';

    /**
     * @var string
     */
    private $fullCommand = '';

    /**
     * @var int
     */
    private $masterPid = 0;

    /**
     * @var string
     */
    private $commandFile = '@runtime/swoft-queue.command';

    /**
     * bean配置赋值
     *
     * @var array
     */
    private $queue = [];

    /**
     * @var array
     */
    private $workerQueue = [];


    /**
     * queueKey 任意映射一个 workerId
     * @var array
     */
    public $queueWorkerId = [];

    /**
     * queueKey 映射 queueName
     *
     * @var array
     */
    public $queueKeyBindQueueName = [];

    /**
     * Start process pool
     *
     * @throws ProcessException
     */
    public function start(): void
    {
        // 统计进程数量
        $this->workerNum = 0;
        foreach ($this->queue as $key => $queueInfo) {
            $num = (int)($queueInfo['worker_num'] ?? 1);
            $queueKey = (int)($queueInfo['queue_key'] ?? 1);
            $this->workerNum += $num;

            // 分配workerId=>queueName
            for ($workerId = $this->workerNum; $workerId > ($this->workerNum - $num); $workerId--) {
                $this->workerQueue[$workerId] = $key;
                $this->queueWorkerId[$queueKey] = $workerId;
            }

            // 映射建立
            $this->queueKeyBindQueueName[$queueKey][$key] = $queueInfo;
        }

        // 检查redis key 是否设置正常
        $checkKeys = [];
        foreach ($this->queueKeyBindQueueName as $queueKey => $arr) {
            foreach ($arr as $queue => $info) {
                $redisKey = $info['redis_key'] ?? "queue";
                if (isset($checkKeys[$redisKey])) {
                    if ($checkKeys[$redisKey] != $queueKey) {
                        throw new ProcessException(sprintf('一个redis key 只能对应一个queue_key； = %d', $queueKey));
                    }
                }
                $checkKeys[$redisKey] = $queueKey;
            }
        }

        // 设置 Pool
        $this->pool = new Pool($this->workerNum + 1, $this->ipcType, $this->msgQueueKey, $this->coroutine);
        foreach ($this->on as $name => $listener) {
            $listenerInterface = SwooleEvent::LISTENER_MAPPING[$name] ?? '';
            if (empty($listenerInterface)) {
                throw new ProcessException(sprintf('Process listener(%s) is not exist!', $name));
            }

            if (!$listener instanceof $listenerInterface) {
                throw new ProcessException(sprintf('Listener(%s) must be instanceof %s', $name, $listenerInterface));
            }

            $listenerMethod = sprintf('on%s', ucfirst($name));
            $this->pool->on($name, [$listener, $listenerMethod]);
        }

        // Set process name
        $this->setProcessName();

        self::$processPool = $this;

        $this->pool->start();
    }

    /**
     * @return bool
     */
    public function reload(): bool
    {
        if (($pid = $this->masterPid) < 1) {
            return false;
        }

        // SIGUSR1 to reload
        return ServerHelper::sendSignal($pid, 10);
    }

    /**
     * @return bool
     */
    public function stop(): bool
    {
        $pid = $this->getPid();
        if ($pid < 1) {
            return false;
        }

        // SIGTERM = 15
        if (ServerHelper::killAndWait($pid, 15, $this->pidName)) {
            $rmPidOk = ServerHelper::removePidFile(alias($this->pidFile));
            $rmCmdOk = ServerHelper::removePidFile(alias($this->commandFile));

            return $rmPidOk && $rmCmdOk;
        }

        return false;
    }

    /**
     * Quick restart
     * @throws SwoftException
     */
    public function restart(): void
    {
        if ($this->isRunning()) {
            // Restart command
            $command = Co::readFile(alias($this->commandFile));

            // Stop server
            $this->stop();

            // Exe restart shell
            Coroutine::exec($command);

            CLog::info('Restart success(%s)!', $command);
        }
    }

    /**
     * @param Pool $pool
     */
    public function initProcessPool(Pool $pool): void
    {
        // Set process
        Sys::setProcessTitle(sprintf('%s-%s', $this->pidName, 'worker'));

        // Save PID to file
        $pidFile = alias($this->pidFile);
        Dir::make(dirname($pidFile));
        file_put_contents($pidFile, $pool->master_pid);

        // Save pull command to file
        $commandFile = alias($this->commandFile);
        Dir::make(dirname($commandFile));
        file_put_contents($commandFile, $this->fullCommand);
    }

    /**
     * Check if process pool is running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        $pidFile = alias($this->pidFile);

        // Is pid file exist ?
        if (file_exists($pidFile)) {
            // Get pid file content and parse the content
            $masterPid = file_get_contents($pidFile);

            // Format type
            $masterPid = (int)$masterPid;

            $this->masterPid = $masterPid;

            // Notice: skip pid 1, resolve start server on docker.
            return $masterPid > 1 && Process::kill($masterPid, 0);
        }

        return false;
    }

    /**
     * Set server, run server on the background
     *
     * @param bool $yes
     *
     * @return $this
     */
    public function setDaemonize(bool $yes = true): self
    {
        if ($yes) {
            Process::daemon(true, false);
        }

        return $this;
    }

    /**
     * @param string $scriptFile
     */
    public function setScriptFile(string $scriptFile): void
    {
        $this->scriptFile = $scriptFile;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->masterPid;
    }

    /**
     * @param string $fullCommand
     */
    public function setFullCommand(string $fullCommand): void
    {
        $this->fullCommand = $fullCommand;
    }

    /**
     * @return string
     */
    public function getPidName(): string
    {
        return $this->pidName;
    }

    /**
     * @return string
     */
    public function getPidFile(): string
    {
        return $this->pidFile;
    }

    /**
     * Set process name
     */
    private function setProcessName(): void
    {
        Sys::setProcessTitle(sprintf('%s-%s', $this->pidName, 'master'));
    }

    /**
     * @param int $workerId
     * @return array
     */
    public function getQueue(int $workerId): array
    {
        $key = $this->workerQueue[$workerId];
        return $this->queue[$key];
    }
}
