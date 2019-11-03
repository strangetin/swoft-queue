<?php


namespace Swoft\Queue\Contract;


use Swoole\Process\Pool;

interface QueueInterface
{
    /**
     * Run
     *
     * @param Pool
     * @param int  $workerId
     */
    public function run(Pool $pool, int $workerId): void;
}
