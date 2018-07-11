<?php

namespace App\Subscriber\Spoiler;

use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use React\EventLoop\Timer\Timer;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove spoiler channel messages after X messages
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class TimedSubsciber implements EventSubscriberInterface
{
    const TIMEOUT = 300;

    /**
     * @var Timer
     */
    private static $timer;

    /**
     * @var SymfonyStyle
     */
    private static $io;

    /**
     * @var int
     */
    private $spoilerChannelId;

    /**
     * ValidateSubscriber constructor.
     *
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
        $client = $message->client;
        self::$io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        if (self::$timer) {
            $client->cancelTimer(self::$timer);
        }
        self::$timer = $client->addTimer(self::TIMEOUT, [$this, 'clean']);
        $io->writeln(sprintf('Spoiler timer reset to %s seconds', self::TIMEOUT));
    }

    /**
     * @param Client $client
     */
    public function clean(Client $client)
    {
        /** @var TextChannel $channel */
        $channel = $client->channels->get($this->spoilerChannelId);
        $messages = $channel->messages->all();
        $count = count($messages);
        array_map(
            function (Message $message) {
                $message->delete();
            },
            $messages
        );
        self::$io->success(sprintf('Removed %s messages from the spoiler channel', $count));
    }
}
