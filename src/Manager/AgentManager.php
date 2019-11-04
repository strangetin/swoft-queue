<?php


namespace Swoft\Queue\Manager;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Log\Helper\CLog;
use Swoft\Queue\QueuePool;
use Swoft\Redis\Redis;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Process\Pool;

/**
 * Job代理转发
 * @package Swoft\Queue\Manager;
 * @Bean()
 */
class AgentManager
{
    /**
     * 维护swoole queue的任务个数上限
     * 到达上限后，不再连续从redis读取
     * 上限后，没半秒检查是否有消费，如果减少，再补充任务
     *
     * @var int
     */
    public static $queueLimit = 1000;


    /**
     * @param Pool $pool
     * @param int $workerId ==0
     * @throws \Swoft\Redis\Exception\RedisException
     */
    public function run(Pool $pool, int $workerId): void
    {
        $queueKeyBindQueueName = QueuePool::$processPool->queueKeyBindQueueName;
        $lastArr = array_pop($queueKeyBindQueueName);

        foreach ($queueKeyBindQueueName as $infoArr) {

            $this->toPop($pool, $infoArr);
        }

        $this->toPop($pool, $lastArr);
    }

    /**
     * @param Pool $pool
     * @param array $infoArr
     * @throws \Swoft\Redis\Exception\RedisException
     */
    private function toPop(Pool $pool, array $infoArr)
    {
        $queueKey = 1;
        $redisKeys = [];
        foreach ($infoArr as $info) {
            $redisKeys[] = $info['redis_key'] ?? "queue";
            $queueKey = $info['queue_key'] ?? 1;
        }

        /** @var Process $queueManagerProcess */
        $queueManagerProcess = $pool->getProcess($this->getWorkerIdForQueueKey($queueKey));
        $queueManagerProcess->useQueue($queueKey, 2);

        $redis = Redis::connection();
        ini_set('default_socket_timeout', -1);
        while (QueuePool::$running) {
            $arr = $queueManagerProcess->statQueue();
            $queueNum = $arr['queue_num'];

            if ($queueNum < self::$queueLimit) {
                for ($i = $queueNum; $i <= self::$queueLimit; $i++) {
                    $msg = $redis->brPop($redisKeys, 0);
                    $queueManagerProcess->push($msg[1]);
                }
            }
            Coroutine::sleep(0.5);
        }
    }

    /**
     * 根据queueKey获取对应的workerId
     *
     * @param string $queueKey
     * @return int
     */
    private function getWorkerIdForQueueKey(string $queueKey): int
    {
        return QueuePool::$processPool->queueWorkerId[$queueKey];
    }
}
