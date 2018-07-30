<?php

namespace App\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Event\MessageReceivedEvent;
use App\Message\RewatchNomination;
use Jikan\MyAnimeList\MalClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc rewatch ranking';

    /**
     * @var int
     */
    private $rewatchChannelId;

    /**
     * @var MalClient
     */
    private $jikan;

    /**
     * @var MessageReceivedEvent
     */
    private $event;

    /**
     * ValidateSubscriber constructor.
     *
     * @param int       $rewatchChannelId
     * @param MalClient $jikan
     */
    public function __construct(
        int $rewatchChannelId,
        MalClient $jikan
    ) {
        $this->rewatchChannelId = $rewatchChannelId;
        $this->jikan = $jikan;
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
        $this->event = $event;
        $message = $event->getMessage();
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $rewatch = new RewatchChannel($message->client->channels->get($this->rewatchChannelId), $this->jikan);
        $message->channel->startTyping();
        $rewatch->getNominations()
            ->then(\Closure::fromCallable([$this, 'onMessagesLoaded']));
    }

    /**
     * @param RewatchNomination[] $nominations
     */
    private function onMessagesLoaded(array $nominations): void
    {
        $io = $this->event->getIo();
        $message = $this->event->getMessage();
        $nominationCount = count($nominations);
        if (!$nominationCount) {
            $message->reply('Er zijn nog geen nominaties!');
            $io->writeln('No nominations yet', $nominationCount);

            return;
        }
        $output = ['De huidige rewatch ranking is'];
        foreach ($nominations as $i => $nomination) {
            $anime = $nomination->getAnime();
            $output[] = sprintf(
                ":tv: %s) **%s** (**%s** votes) door **%s**\neps: *%s* | score: *%s* | aired: *%s*",
                $i + 1,
                $anime->getTitle(),
                $nomination->getVotes(),
                $nomination->getAuthor(),
                $anime->getEpisodes(),
                $anime->getScore(),
                (string)$anime->getAired()
            );
        }
        $message->channel->stopTyping(true);
        $message->channel->send(implode(PHP_EOL, $output));
        $io->success('Ranking displayed');
    }
}
