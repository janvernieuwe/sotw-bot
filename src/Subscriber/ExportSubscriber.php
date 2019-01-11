<?php

namespace App\Subscriber;

use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\MessageReaction;
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
        if ($message->content !== self::COMMAND || !$event->isAdmin()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $message->channel->fetchMessages()
            ->done(
                function ($messages) use ($io) {
                    if (!$fp = fopen('php://memory', 'wb')) {
                        $io->error('Cannot open fp');
                    }
                    /** @var Message $message */
                    foreach ($messages as $message) {
                        $count = 0;
                        if ($message->reactions->count()) {
                            /** @var MessageReaction $reaction */
                            $reaction = $message->reactions->first();
                            $count = $reaction->count;
                        }
                        fputcsv($fp, [$count, str_replace(',', '\,', $message->content)]);
                    }
                    rewind($fp);
                    $contents = stream_get_contents($fp);
                    $message->reply(
                        'Here is your export ',
                        ['files' => [['name' => 'export.csv', 'data' => $contents]]]
                    );
                }
            );

        $io->success('Exported the channel');
    }
}
