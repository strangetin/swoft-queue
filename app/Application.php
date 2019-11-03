<?php declare(strict_types=1);

namespace App;

use Swoft\SwoftApplication;
use function date_default_timezone_set;

/**
 * Class Application
 *
 * @since 2.0
 */
class Application extends SwoftApplication
{
    public function getCLoggerConfig(): array
    {
        return [
            'name'    => 'swoft',
            'enable'  => true,
            'output'  => true,
//            'levels'  => 'debug,info,notice,warning,error,critical,alert,emergency',
            'levels'  => '',
            'logFile' => ''
        ];
    }

    protected function beforeInit(): void
    {
        parent::beforeInit();

        date_default_timezone_set('Asia/Shanghai');
    }
}
