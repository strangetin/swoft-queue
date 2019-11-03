<?php declare(strict_types=1);


namespace Swoft\Queue\Swoole;


use Swoft;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Queue\Contract\WorkerStartInterface;
use Swoft\Queue\ProcessEvent;
use Swoft\Queue\Manager\QueueManager;
use Swoft\Queue\QueuePool;
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
}
