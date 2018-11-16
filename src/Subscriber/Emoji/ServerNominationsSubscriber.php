<?php

namespace App\Subscriber\Emoji;

use App\Event\MessageReceivedEvent;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\Emoji;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Display the current bikkel ranking
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class ServerNominationsSubscriber implements EventSubscriberInterface
{
    private const CMD = '!haamc emoji server';
    private static $emoji;

    /**
     * @var int
     */
    private $channelId;

    /**
     * NominationSubscriber constructor.
     *
     * @param int $emojiChannelId
     *
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
        $emoji = $message->guild->emojis->all();
        if (count($emoji)) {
            $this->addEmoji($emoji, $channel, $io);
        }
        $message->delete();
    }

    /**
     * @param array        $stack
     * @param TextChannel  $channel
     * @param SymfonyStyle $io
     */
    public function addEmoji(array $stack, TextChannel $channel, SymfonyStyle $io): void
    {
        if (!count($stack)) {
            return;
        }
        $emoji = array_shift($stack);
        $channel->send(Util::emojiToString($emoji))->done(
            function (Message $emojiPost) use ($emoji, $io, $channel, $stack) {
                $emojiPost->react(Util::emojiToString($emoji))
                    ->done(
                        function () use ($emoji, $io, $channel, $stack) {
                            $io->success(sprintf('Emoji %s nominated', $emoji->name));
                            $this->addEmoji($stack, $channel, $io);
                        }
                    );
            }
        );
    }
}
