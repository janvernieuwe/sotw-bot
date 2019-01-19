<?php

namespace App\Subscriber;

use App\Command\CommandParser;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\MessageReaction;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExportSubscriber
 *
 * @package App\Subscriber
 */
class ExportSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc export';

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

        $channel->fetchMessages(['limit' => 100])
            ->done(
                function ($messages) use ($io, $message, $channel) {
                    if (!$fp = fopen('php://memory', 'wb')) {
                        $io->error('Cannot open fp');
                    }
                    /** @var Message $msg */
                    foreach ($messages as $msg) {
                        $count = 0;
                        if ($msg->reactions->count()) {
                            /** @var MessageReaction $reaction */
                            $reaction = $msg->reactions->first();
                            $count = $reaction->count;
                        }
                        fputcsv(
                            $fp,
                            [
                                $count,
                                $msg->author->username,
                                str_replace(',', '\,', $msg->content),
                                $msg->createdAt->format('Y-m-d H:i:s')
                            ]
                        );
                    }
                    rewind($fp);
                    $contents = stream_get_contents($fp);
                    $message->reply(
                        'Here is your export ',
                        ['files' => [['name' => $channel->name.'_export.csv', 'data' => $contents]]]
                    );
                }
            );

        $io->success('Exported channel: '.$channel->name);
    }
}
