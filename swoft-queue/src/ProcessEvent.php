<?php declare(strict_types=1);


namespace Swoft\Queue;

/**
 * Class ProcessEvent
 *
 * @since 2.0
 */
class ProcessEvent
{
    /**
     * Before process
     */
    public const BEFORE_PROCESS = 'swoft.queue.before';

    /**
     * After process
     */
    public const AFTER_PROCESS = 'swoft.queue.after';
}
