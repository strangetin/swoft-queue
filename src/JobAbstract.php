<?php


namespace Swoft\Queue;

/**
 * Class JobAbstract
 * @package Swoft\Queue
 */
abstract class JobAbstract
{
    /**
     * 业务处理
     *
     * @param $msg
     */
    abstract public function handle($msg): void;
}
