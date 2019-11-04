# Swoft Process

基于redis的队列组件


## 数据流程
![image](https://github.com/ctfang/images/blob/master/queue/QQ%E5%9B%BE%E7%89%8720191103225258.png)

## Install

- composer command

```bash
composer require ctfang/swoft-queue
```

## 配置,在`bean.php`新增
```bash
'queueServer' => [
    'class' => QueuePool::class,
    'queue' => [
        'test' => [ // test 队列名称
           'class' => JobsManager::class,// job分发类
           'worker_num' => 4,// 队列开启进程数量,设置比cpu数量多一两个就好了
           'coroutine_num' => 1,// 每个进程内同时处理多少个job
           'queue_key' => 1,// swoole内置功能queue_key,int类型
           'redis_key' => "queue",// redis key
        ],
    ],
]
```


## 启动
```bash
php bin/swoft queue:start
```
## Job 处理工作
job是处理任务的最小单位，job是挂靠在queue上的，每个queue可以挂靠很多Job

```php
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

        Coroutine::sleep(5);

        CLog::info($id." 结束 ");
    }
}
```

## 生成者

```php
$queue = "test";
$job   = "demo";
$msg   = ['str'=>"这里传入Job的内容"];
$push  = [$queue,$job,$msg];
// 'queue' 是配置对应的 redis_key 值 
Redis::lPush('queue',json_encode($push));
```

## LICENSE

The Component is open-sourced software licensed under the [Apache license](LICENSE).
