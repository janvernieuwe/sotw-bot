<?php

namespace App\Yasmin\Subscriber;

use App\Command\Yasmin\RunCommand;
use App\Yasmin\Event\MessageReceivedEvent;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Help command for normal users
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class UptimeSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc uptime';

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if ($message->content !== self::COMMAND) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $seconds = time() - RunCommand::$start;
        $a = new DateTimeImmutable();
        $b = $a->add(new DateInterval("PT{$seconds}S"));
        $uptime = $b->diff($a)->format('%d days, %h hours, %i minutes, %s seconds');
        $message->channel->send($uptime);
        $io->success($uptime);
    }
}
