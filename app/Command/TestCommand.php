<?php


namespace App\Command;

use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Log\Helper\Log;
use Swoft\Redis\Redis;

/**
 * Class TestCommand
 * @package App\Command
 * @Command(name="test")
 */
class TestCommand
{
    /**
     * @CommandMapping(name="run")
     */
    public function test()
    {
        for ($i = 0; $i <= 100000; $i++) {
            Redis::lPush('queue',json_encode(["test","demo",["time"=>time(),'num'=>$i]]));
        }
    }
}
