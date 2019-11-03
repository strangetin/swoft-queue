<?php


namespace Swoft\Queue;

use Swoole\Process\Pool;

/**
 * Class QueueAbstract
 * @package Swoft\Queue
 */
abstract class QueueAbstract
{
    /**
     * 监听swoole提供进程队列
     *
     * @var int
     */
    protected $queueKey = 1;

    /**
     * 队列模式
     *
     * @var int
     */
    protected $mod = 2;

    /**
     * 每个进程数量,同时处理N个Job
     *
     * @var int
     */
    protected $coroutineNum = 10;

    /**
     * swoole分配的workerId
     *
     * @var int
     */
    protected $workerId = 0;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @return int
     */
    public function getQueueKey(): int
    {
        return $this->queueKey;
    }

    /**
     * @param  int  $key
     */
    public function setQueueKey(int $key)
    {
        $this->queueKey = $key;
    }

    /**
     * @return int
     */
    public function getCoroutineNum(): int
    {
        return $this->coroutineNum;
    }


    /**
     * 设置初始化多少个协程，同时处理多少个Job
     *
     * @param int $num
     */
    public function setCoroutineNum(int $num)
    {
        $this->coroutineNum = $num;
    }

    /**
     * @param  int  $workerId
     */
    protected function setWorkerId(int $workerId)
    {
        $this->workerId = $workerId;
    }

    /**
     * @param $pool
     */
    protected function setPool(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @param  Pool  $pool
     * @param  int  $workerId
     */
    public function run(Pool $pool, int $workerId): void
    {
        $this->setWorkerId($workerId);
        $this->setPool($pool);

        $this->start();

        $process = $pool->getProcess($workerId);
        $process->useQueue($this->getQueueKey(), $this->mod);

        // 设置了同时处理多个任务
        for ($i = 1; $i < $this->getCoroutineNum(); $i++) {
            sgo(function () use ($process) {
                $this->toHandle($process);
            });
        }
        // 最后一个
        $this->toHandle($process);
    }

    /**
     * @param $process
     */
    private function toHandle($process): void
    {
        while (true) {
            $msg = $process->pop();
            $this->handle($msg);
        }
    }

    /**
     * 运行初始化
     */
    abstract protected function start(): void;

    /**
     * 队列信息处理
     *
     * @param $frame
     */
    abstract protected function handle($frame): void;
}
