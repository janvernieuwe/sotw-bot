<?php

namespace App\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Event\MessageReceivedEvent;
use App\Formatter\BBCodeFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class ForumSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc sotw forum';

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [];
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $nominations = $this->sotw->getLastNominations();
        $formatter = new BBCodeFormatter($nominations);
        $bbcode = '```'.$formatter->createMessage().'```';
        $message->channel->send($bbcode);
        $io->success('Displayed the forum post');
    }
}
