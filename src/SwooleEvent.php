<?php declare(strict_types=1);


namespace Swoft\Queue;

use Swoft\Queue\Contract\WorkerStartInterface;
use Swoft\Queue\Contract\WorkerStopInterface;

/**
 * Class SwooleEvent
 *
 * @since 2.0
 */
class SwooleEvent
{
    /**
     * Worker start
     */
    public const WORKER_START = 'workerStart';

    /**
     * Worker stop
     */
    public const WORKER_STOP = 'workerStop';

    /**
     * Listener mapping
     */
    public const LISTENER_MAPPING = [
        self::WORKER_START => WorkerStartInterface::class,
        self::WORKER_STOP  => WorkerStopInterface::class
    ];
}
