<?php

use Swoft\Queue\Manager\JobsManager;
use Swoft\Queue\QueuePool;
use Swoft\Queue\Swoole\WorkerStartListener;
use Swoft\Queue\Swoole\WorkerStopListener;
use Swoft\Queue\SwooleEvent;
use Swoft\Redis\RedisDb;

return [
    'noticeHandler'      => [
        'logFile' => '@runtime/logs/notice-%d{Y-m-d-H}.log',
    ],
    'applicationHandler' => [
        'logFile' => '@runtime/logs/error-%d{Y-m-d}.log',
    ],
    'logger' => [
        'flushRequest' => false,
        'enable' => true,
        'json' => false,
    ],
    'queueServer' => [
        'class' => QueuePool::class,
        'on' => [
            SwooleEvent::WORKER_START => bean(WorkerStartListener::class),
            SwooleEvent::WORKER_STOP => bean(WorkerStopListener::class)
        ],
        'queue' => [
            'test' => [
                'class' => JobsManager::class,
                'worker_num' => 4,
                'coroutine_num' => 1,
                'queue_key' => 1,
                'redis_key' => "queue",
            ],
//            'log' => [
//                'class' => JobsManager::class,
//                'worker_num' => 4,
//                'coroutine_num' => 1,
//                'queue_key' => 1,
//                'redis_key' => "queue",
//            ],
        ],
    ],
    'redis' => [
        'class' => RedisDb::class,
        'host' => env("REDIS_HOST"),
        'port' => env("REDIS_PORT"),
        'password' => env('REDIS_PASSWORD'),
        'database' => env("REDIS_DB", 0),
        'option' => [
            'prefix' => ''
        ]
    ],
    'redis.pool' => [
        'class' => \Swoft\Redis\Pool::class,
        'database' => bean('redis')
    ],
];
