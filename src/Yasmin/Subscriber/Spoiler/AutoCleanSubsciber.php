<?php

namespace App\Yasmin\Subscriber\Spoiler;

use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Utils\Collection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove spoiler channel messages after X messages
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class AutoCleanSubsciber implements EventSubscriberInterface
{
    const MESSAGE_LIMIT = 20;

    /**
     * @var bool
     */
    private static $cleaning = false;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var int
     */
    private $spoilerChannelId;

    /**
     * ValidateSubscriber constructor.
     * @param int $spoilerChannelId
     */
    public function __construct(int $spoilerChannelId)
    {
        $this->spoilerChannelId = $spoilerChannelId;
    }

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
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->spoilerChannelId) {
            return;
        }
        // Don't run too much at once
        if (self::$cleaning) {
            $event->getIo()->writeln('Already cleaning.');

            return;
        }
        self::$cleaning = true;
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $this->io = $event->getIo();
        $message->channel->fetchMessages()->done([$this, 'onChannelMessages']);
    }

    /**
     * @param Collection|Message[] $channelMessages
     */
    public function onChannelMessages(Collection $channelMessages)
    {
        $count = $channelMessages->count();
        if ($count <= 20) {
            $this->io->writeln(sprintf('Not enough messages %s', $count));

            return;
        }
        $lastMessages = $channelMessages->all();
        $lastMessages = array_splice($lastMessages, 21);
        foreach ($lastMessages as $message) {
            $message->delete();
        }
        self::$cleaning = false;
        $this->io->writeln('Oldest message removed');
    }
}
