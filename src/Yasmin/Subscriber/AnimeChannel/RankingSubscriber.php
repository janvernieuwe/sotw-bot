<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Message\JoinableChannelMessage;
use App\Util\Util;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Utils\Collection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    const LIMIT = 10;
    private static $offset = 0;
    /**
     * @var Message
     */
    private $message;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var TextChannel
     */
    private $channel;

    /**
     * @var int
     */
    private $seasonalChannelId;

    /**
     * RankingSubscriber constructor.
     * @param int $seasonalChannelId
     */
    public function __construct($seasonalChannelId)
    {
        $this->seasonalChannelId = $seasonalChannelId;
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
        $this->message = $message = $event->getMessage();
        if (strpos($message->content, '!haamc season ranking') !== 0) {
            return;
        }
        preg_match('/\d+$/', $message->content, $matches);
        self::$offset = $matches[0] ?? 0;
        $this->io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $client = $event->getMessage()->client;
        /** @var TextChannel $channel */
        $this->channel = $channel = $client->channels->get($this->seasonalChannelId);
        $channel->fetchMessages()->done([$this, 'onMessagesLoaded']);
    }

    /**
     * @param Collection $messages
     */
    public function onMessagesLoaded(Collection $messages)
    {
        $series = [];
        /** @var Message $message */
        foreach ($messages->all() as $message) {
            if (!JoinableChannelMessage::isJoinChannelMessage($message)) {
                continue;
            }
            $animeChannel = new JoinableChannelMessage($message);
            $series[] = $animeChannel;
        }
        uasort(
            $series,
            function (JoinableChannelMessage $a, JoinableChannelMessage $b) {
                return $a->getWatchers() < $b->getWatchers();
            }
        );
        $series = array_values($series);
        $this->message->channel->send(self::createRanking($series, $this->seasonalChannelId));
        $this->io->success('Seasonal ranking displayed');
    }

    /**
     * @param array|JoinableChannelMessage[] $channels
     * @param int $channelId
     * @return string
     */
    public static function createRanking(array $channels, int $channelId): string
    {
        $fields = ['__**HAAMC Seasonal Anime Ranking**__ Join de channels in '.Util::channelLink($channelId)];
        $channels = array_slice($channels, self::$offset * self::LIMIT, 10);
        foreach ($channels as $i => $channel) {
            $fields[] = sprintf(
                ':film_frames:  #%s **%s** (%s), %s kijkers',
                $i + 1 + (self::$offset * self::LIMIT),
                $channel->getAnimeTitle(),
                Util::channelLink($channel->getChannelId()),
                $channel->getWatchers()
            );
        }

        return implode(PHP_EOL, $fields);
    }
}
