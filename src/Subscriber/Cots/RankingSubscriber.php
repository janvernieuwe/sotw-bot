<?php

namespace App\Subscriber\Cots;

use App\Channel\CotsChannel;
use App\Event\MessageReceivedEvent;
use App\Message\CotsNomination;
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
    public const COMMAND = '!haamc cots ranking';

    /**
     * @var MessageReceivedEvent
     */
    private $event;

    /**
     * @var int
     */
    private $cotsChannelId;

    /**
     * @var MalClient
     */
    private $jikan;

    /**
     * RankingSubscriber constructor.
     *
     * @param int       $cotsChannelId
     * @param MalClient $jikan
     */
    public function __construct(int $cotsChannelId, MalClient $jikan)
    {
        $this->cotsChannelId = $cotsChannelId;
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
        $cotsChannel = new CotsChannel($this->jikan, $message->client->channels->get($this->cotsChannelId));
        $cotsChannel->getLastNominations()
            ->then(\Closure::fromCallable([$this, 'onMessagesLoaded']));
    }

    /**
     * @param CotsNomination[] $nominations
     */
    private function onMessagesLoaded(array $nominations): void
    {
        $io = $this->event->getIo();
        $message = $this->event->getMessage();
        if (!count($nominations)) {
            $message->reply('Er zijn nog geen nominaties');
            $io->error('Er zijn nog geen nominaties');

            return;
        }
        $output = [];
        $top10 = \array_slice($nominations, 0, 10);
        foreach ($top10 as $i => $nomination) {
            $voiceActors = $nomination->getCharacter()->getVoiceActors();
            $output[] = sprintf(
                ":mens: %s) **%s**, *%s*\nvotes: **%s** | door: *%s* | voice actor: *%s* | score: %s",
                $i + 1,
                $nomination->getCharacter()->getName(),
                $nomination->getAnime()->getTitle(),
                $nomination->getVotes(),
                $nomination->getAuthor(),
                count($voiceActors) ? $nomination->getCharacter()->getVoiceActors()[0]->getName() : 'n/a',
                $nomination->getAnime()->getScore()
            );
        }
        $message->channel->send(implode(PHP_EOL, $output));
        $io->success('Ranking displayed');
    }
}
