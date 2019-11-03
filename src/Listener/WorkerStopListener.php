<?php


namespace Swoft\Queue\Listener;


use Swoft\Bean\BeanFactory;
use Swoft\Co;
use Swoft\Event\Annotation\Mapping\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\Log\Helper\CLog;
use Swoft\Queue\QueueAbstract;
use Swoft\Queue\QueuePool;
use Swoft\Queue\SwooleEvent;
use Swoole\Coroutine;
use Swoole\Process\Pool;

/**
 * worker 停止事件
 *
 * @package Swoft\Queue\Listener
 * @Listener(event=SwooleEvent::WORKER_STOP)
 */
class WorkerStopListener implements EventHandlerInterface
{
    /**
     * @param EventInterface $event
     */
    public function handle(EventInterface $event): void
    {
        QueuePool::$running = false;

        [, $workerId] = $event->getParams();

        CLog::info("worker(%d) 停止中。。。", $workerId);
        \Swoole\Event::wait();
        CLog::info("worker(%d) 已经停止", $workerId);
    }
}
