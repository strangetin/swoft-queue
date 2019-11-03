<?php


namespace App\Job;

use Swoft\Log\Helper\CLog;
use Swoft\Log\Helper\Log;
use Swoft\Queue\Annotation\Mapping\Job;
use Swoft\Queue\JobAbstract;
use Swoole\Coroutine;

/**
 * Class NewFeedJob
 * @package App\Job
 * @Job(queue="test",name="demo")
 */
class TestJob extends JobAbstract
{
    /**
     * 业务处理
     *
     * @param $msg
     */
    public function handle($msg): void
    {
        $id = uniqid();

        CLog::info($id." 启动 ".json_encode($msg));
        Log::error($id." 启动 ");

        Coroutine::sleep(10);

        Log::error($id." 结束 ");
    }
}
