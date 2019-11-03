<?php declare(strict_types=1);


namespace Swoft\Queue;


use Swoft\Queue\Swoole\WorkerStartListener;
use Swoft\Queue\Swoole\WorkerStopListener;
use Swoft\SwoftComponent;

/**
 * Class AutoLoader
 *
 * @since 2.0
 */
class AutoLoader extends SwoftComponent
{
    /**
     * @return bool
     */
    public function enable(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getPrefixDirs(): array
    {
        return [
            __NAMESPACE__ => __DIR__,
        ];
    }

    /**
     * @return array
     */
    public function beans(): array
    {
        return [
            'queueServer' => [
                'class' => QueuePool::class,
                'on' => [
                    SwooleEvent::WORKER_START => bean(WorkerStartListener::class),
                    SwooleEvent::WORKER_STOP => bean(WorkerStopListener::class)
                ],
                'queue' => [
//                    'log' => [
//                       'class' => JobsManager::class,
//                       'worker_num' => 4,
//                       'coroutine_num' => 1,
//                       'queue_key' => 1,
//                       'redis_key' => "queue",
//                    ],
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    public function metadata(): array
    {
        return [];
    }
}
