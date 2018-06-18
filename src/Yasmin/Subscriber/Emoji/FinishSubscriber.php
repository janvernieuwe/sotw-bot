<?php

namespace App\Yasmin\Subscriber\Emoji;

use App\Message\YasminEmojiNomination;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Utils\Collection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Display the current bikkel ranking
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    private const CMD = '!haamc emoji finish';

    /**
     * @var SymfonyStyle
     */
    private static $io;

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
        self::$io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');

        $channel = $message->guild->channels->get($this->channelId);
        $channel->fetchMessages()->done(
            function (Collection $x) {
                //print_r($x->count());
            }
        );
    }

    /**
     * @param array|Message[] $messages
     * @return array
     */
    private function getValidMessages(array $messages): array
    {
        $messages = array_map(
            function (Message &$message) {
                $message = new YasminEmojiNomination($message);
            },
            $messages
        );

        return array_filter(
            $messages,
            function (YasminEmojiNomination $message) {
                return $message->isValid();
            }
        );
    }
}
