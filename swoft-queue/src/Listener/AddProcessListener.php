<?php declare(strict_types=1);


namespace Swoft\Queue\Listener;


use Swoft;
use Swoft\Event\Annotation\Mapping\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\Log\Error;
use Swoft\Queue\Contract\UserProcessInterface;
use Swoft\Queue\Process;
use Swoft\Queue\ProcessEvent;
use Swoft\Server\Exception\ServerException;
use Swoft\Server\Server;
use Swoft\Server\ServerEvent;
use Swoft\Stdlib\Helper\PhpHelper;
use Swoole\Process as SwooleProcess;
use Throwable;

/**
 * Class AddProcessListener
 *
 * @since 2.0
 *
 * @Listener(event=ServerEvent::ADDED_PROCESS)
 */
class AddProcessListener implements EventHandlerInterface
{
    /**
     * @param EventInterface $event
     *
     * @throws ServerException
     */
    public function handle(EventInterface $event): void
    {
        /* @var Server $server */
        $server = $event->getTarget();

        $this->addProcess($server);
    }

    /**
     * Add process
     *
     * @param Server $server
     *
     * @throws ServerException
     */
    private function addProcess(Server $server): void
    {
        $process = $server->getProcess();
        if (empty($process)) {
            return;
        }

        foreach ($process as $name => $userProcess) {
            if (!$userProcess instanceof UserProcessInterface) {
                throw new ServerException('Server add process must be instanceof UserProcessInterface!');
            }

            $callback  = [$userProcess, 'run'];
            $stdinOut  = $userProcess->isStdinOut();
            $pipeType  = $userProcess->getPipeType();
            $coroutine = $userProcess->isCoroutine();

            $function = function (SwooleProcess $process) use ($callback, $server, $name) {
                $process = Process::new($process);

                // Before
                Swoft::trigger(ProcessEvent::BEFORE_USER_PROCESS, null, $server, $process, $name);

                try {// Run
                    PhpHelper::call($callback, $process);
                } catch (Throwable $e) {
                    Error::log('User process fail(%s %s %d)!', $e->getFile(), $e->getMessage(), $e->getLine());
                }

                // After
                Swoft::trigger(ProcessEvent::AFTER_USER_PROCESS);
            };

            $process = new SwooleProcess($function, $stdinOut, $pipeType, $coroutine);
            $server->getSwooleServer()->addProcess($process);
        }
    }
}
