<?php

namespace App\Yasmin\Subscriber\Emoji;

use App\Util\Util;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Emoji;
use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Display the current bikkel ranking
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class ServerNominationsSubscriber implements EventSubscriberInterface
{
    private const CMD = '!haamc emoji server';

    /**
     * @var int
     */
    private $channelId;

    /**
     * NominationSubscriber constructor.
     * @param int $emojiChannelId
     * @internal param int $channelId
     */
    public function __construct(int $emojiChannelId)
    {
        $this->channelId = $emojiChannelId;
    }

    /**
     *
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
        if ($message->content !== self::CMD || !$event->isAdmin()) {
            return;
        }
        $event->stopPropagation();
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');

        $channel = $message->guild->channels->get($this->channelId);
        /** @var Emoji $emoji */
        foreach ($message->guild->emojis->all() as $emoji) {
            $channel->send(Util::emojiToString($emoji))->done(
                function (Message $emojiPost) use ($emoji, $io) {
                    $emojiPost->react(Util::emojiToString($emoji));
                    $io->success(sprintf('Emoji %s nominated', $emoji->name));
                }
            );
        }
        $message->delete();
    }
}
