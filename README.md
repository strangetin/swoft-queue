# Swoft Queue

Компонент очереди на основе Redis


## Добавьте конфигурацию в файл `bean.php`
```bash
'queueServer' => [
    'class' => QueuePool::class,
    'queue' => [
        'test' => [ // test Имя очереди
           'class' => JobsManager::class,// распределитель
           'worker_num' => 4,// Количество открытых очередей процессов, лучше установить на один или два больше, чем количество процессоров
           'coroutine_num' => 1,// Сколько заданий обрабатывается одновременно в каждом процессе
           'queue_key' => 1,// встроенная функция swoole queue_key, тип int
           'redis_key' => "queue",// redis key
        ],
    ],
]
```


## Запуск
```bash
php bin/swoft queue:start
```
## Пример Job

```php
<?php
namespace App\Job;

use Swoft\Log\Helper\CLog;
use Swoft\Log\Helper\Log;
use Swoft\Queue\Annotation\Mapping\Job;
use Swoft\Queue\JobAbstract;
use Swoole\Coroutine;

/**
 * Class TestJob
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

## Запуск задачи в очередь

```php
$queue = "test";
$job   = "demo";
$msg   = ['str'=>"Пример передаваемых данных"];
$push  = [$queue,$job,$msg]; 
Redis::lPush('queue',json_encode($push));
```

## LICENSE

The Component is open-sourced software licensed under the [Apache license](LICENSE).
