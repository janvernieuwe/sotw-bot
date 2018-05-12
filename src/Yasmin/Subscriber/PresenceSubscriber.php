<?php

namespace App\Yasmin\Subscriber;

use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Help command for normal users
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class PresenceSubscriber implements EventSubscriberInterface
{
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
        if (!preg_match('/^\!haamc presence (.*)$/', $message->content, $matches) || !$event->isAdmin()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $presence = $matches[1];
        $client = $message->client;
        $client->user->setPresence(
            [
                'status' => 'online',
                'game'   => [
                    'name' => $presence,
                    'type' => 0,
                ],
            ]
        );
        $io->success(sprintf('Presence set to %s', $presence));
    }
}
