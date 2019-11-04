<?php declare(strict_types=1);


namespace Swoft\Queue\Swoole;


use Swoft;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Queue\Contract\WorkerStartInterface;
use Swoft\Queue\ProcessEvent;
use Swoft\Queue\Manager\QueueManager;
use Swoft\Queue\QueuePool;
use Swoft\Queue\SwooleEvent;
use Swoole\Process\Pool;
use Swoft\Bean\Annotation\Mapping\Inject;


/**
 * Class WorkerStartListener
 *
 * @since 2.0
 *
 * @Bean()
 */
class WorkerStartListener implements WorkerStartInterface
{
    /**
     * @Inject()
     *
     * @var QueueManager
     */
    private $queueManager;


    /**
     * @Inject()
     *
     * @var Swoft\Queue\Manager\AgentManager
     */
    private $agentManager;

    /**
     * @param Pool $pool
     * @param int  $workerId
     *
     */
    public function onWorkerStart(Pool $pool, int $workerId): void
    {
        $this->setSignal($pool,$workerId);
        // Init
        QueuePool::$processPool->initProcessPool($pool);

        // Before
        Swoft::trigger(ProcessEvent::BEFORE_PROCESS, $this, $pool, $workerId);

        if ( $workerId==0 ){
            $this->agentManager->run($pool, $workerId);
        }else{
            $this->queueManager->run($pool, $workerId);
        }

        // After
        Swoft::trigger(ProcessEvent::BEFORE_PROCESS, $this, $pool, $workerId);
    }


    /**
     * 设置信号
     *
     * @param Pool $pool
     * @param int $workerId
     */
    private function setSignal(Pool $pool, int $workerId)
    {
        \Swoole\Process::signal(SIGINT, function () use ($pool, $workerId) {
            Swoft::trigger(SwooleEvent::WORKER_STOP, null, $pool, $workerId);
            $pool->getProcess($workerId)->exit(1);
        });

        \Swoole\Process::signal(SIGTERM, function () use ($pool, $workerId) {
            Swoft::trigger(SwooleEvent::WORKER_STOP, null, $pool, $workerId);
            $pool->getProcess($workerId)->exit(1);
        });
    }
}
