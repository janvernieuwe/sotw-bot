<?php

namespace App\Subscriber;

use App\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class DebugChannelsSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc debug:channels';

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
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        if (!$fp = fopen('php://memory', 'wb')) {
            $io->error('Cannot open fp');
        }

        fputcsv($fp, ['name', 'parent', 'id', 'position']);
        foreach ($message->guild->channels as $channel) {
            fputcsv($fp, [$channel->name, $channel->parentID ?? '', $channel->id, $channel->position]);
        }

        rewind($fp);
        $contents = stream_get_contents($fp);
        $message->reply(
            'Channel debug export ',
            ['files' => [['name' => 'channel_debug.csv', 'data' => $contents]]]
        );
        $io->success('Displayed admin help');
    }
}
