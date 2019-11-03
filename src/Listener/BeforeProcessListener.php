<?php declare(strict_types=1);


namespace Swoft\Queue\Listener;


use Swoft\Context\Context;
use Swoft\Event\Annotation\Mapping\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\Log\Helper\Log;
use Swoft\Queue\Context\ProcessContext;
use Swoft\Queue\ProcessEvent;

/**
 * Class BeforeProcessListener
 *
 * @since 2.0
 *
 * @Listener(event=ProcessEvent::BEFORE_PROCESS)
 */
class BeforeProcessListener implements EventHandlerInterface
{
    /**
     * @param EventInterface $event
     *
     */
    public function handle(EventInterface $event): void
    {
        // var_dump('BeforeProcessListener');
        [$pool, $workerId] = $event->getParams();

        $context = ProcessContext::new($pool, $workerId);
        if (Log::getLogger()->isEnable()) {
            $data = [
                'event'       => 'swoft.queue.worker.start',
                'uri'         => '',
                'requestTime' => microtime(true),
            ];
            $context->setMulti($data);
        }

        Context::set($context);
    }
}
