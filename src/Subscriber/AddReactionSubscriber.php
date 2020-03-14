<?php

namespace App\Subscriber;

use App\Command\CommandParser;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExportSubscriber
 *
 * @package App\Subscriber
 */
class AddReactionSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc addreaction';

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
        if (strpos($message->content, self::COMMAND) !== 0 || !$event->isAdmin()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $command = new CommandParser($message);
        $channelId = $command->parseArgument('channel') ?? $message->channel->getId();
        /** @var TextChannel $channel */
        $channel = $message->client->channels->get($channelId);
        $emoji = trim(preg_replace('/!haamc addreaction /', '', $message->content));
        $message->delete();

        $channel->fetchMessages(['limit' => 100])
            ->done(
                function ($messages) use ($emoji) {
                    /** @var Message $msg */
                    foreach ($messages as $msg) {
                        $msg->react($emoji);
                    }
                }
            );

        $io->success('Exported channel: '.$channel->name);
    }
}
