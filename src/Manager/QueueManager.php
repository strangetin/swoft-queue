<?php


namespace Swoft\Queue\Manager;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\BeanFactory;
use Swoft\Log\Error;
use Swoft\Queue\QueueAbstract;
use Swoft\Queue\QueuePool;
use Swoft\Stdlib\Helper\PhpHelper;
use Swoole\Process\Pool;

/**
 * Class ManagerQueue
 * @package Swoft\Queue\Manager;
 * @Bean()
 */
class QueueManager
{
    /**
     * @param  Pool  $pool
     * @param  int  $workerId
     */
    public function run(Pool $pool, int $workerId): void
    {
        try {
            $queue = QueuePool::$processPool->getQueue($workerId);

            /** @var QueueAbstract $queueManager */
            $queueManager = BeanFactory::getBean($queue['class']);
            $queueManager->setQueueKey($queue['queue_key'] ?? 1);
            $queueManager->setCoroutineNum($queue["coroutine_num"]??10);

            PhpHelper::call([$queueManager, 'run'], $pool, $workerId);
        } catch (\Throwable $e) {
            Error::log(
                sprintf('启动队列失败(%s %s %d)!', $e->getMessage(), $e->getFile(), $e->getLine())
            );
        }
    }
}
