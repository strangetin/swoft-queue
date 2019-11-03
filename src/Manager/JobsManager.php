<?php


namespace Swoft\Queue\Manager;


use chan;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\BeanFactory;
use Swoft\Queue\Annotation\Mapping\Job;
use Swoft\Queue\Exception\ProcessException;
use Swoft\Queue\QueueAbstract;
use Swoft\Queue\QueuePool;
use Swoft\Stdlib\Helper\PhpHelper;

/**
 * Class JobsManager
 * @package Swoft\Queue\Manager;
 * @Bean()
 */
class JobsManager extends QueueAbstract
{
    /**
     * @var array
     */
    public static $jobs = [];

    /**
     * @var array
     */
    protected $jobHandle = [];

    /**
     * 注册Job到队列
     *
     * @param  Job  $jobAnnotation
     * @param  string  $className
     */
    public static function registeredJob(Job $jobAnnotation, string $className)
    {
        self::$jobs[$jobAnnotation->getQueue()][$jobAnnotation->getName()] = $className;
    }

    /**
     * 运行初始化
     */
    protected function start(): void
    {
        $queue = QueuePool::$processPool->getQueue($this->workerId);
        $arr = QueuePool::$processPool->queueKeyBindQueueName[$queue['queue_key']];

        foreach ($arr as $queueName=>$infoArr){
            // 只实例化当前队列下的JOB
            if (isset(self::$jobs[$queueName])) {
                foreach (self::$jobs[$queueName] as $jobName=>$className) {
                    $this->jobHandle[$queueName][$jobName] = BeanFactory::getBean($className);
                }
            }
        }
    }

    /**
     * 队列信息处理
     *
     * @param  string  $frame
     * @throws ProcessException
     */
    protected function handle($frame): void
    {
        $msg = json_decode($frame, true);
        $queue = $msg[0];
        $job = $msg[1];

        if (!isset($this->jobHandle[$queue][$job])) {
            throw new ProcessException(sprintf('找不到Job处理,queue = %s;job = %s', $queue,$job));
        }

        PhpHelper::call([$this->jobHandle[$queue][$job], 'handle'], $msg[2]);
    }
}
