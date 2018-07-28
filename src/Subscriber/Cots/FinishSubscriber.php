<?php

namespace App\Subscriber\Cots;

use App\Channel\Channel;
use App\Channel\CotsChannel;
use App\Event\MessageReceivedEvent;
use App\Exception\RuntimeException;
use App\Message\CotsNomination;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use Jikan\MyAnimeList\MalClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc cots finish';

    /**
     * @var MessageReceivedEvent
     */
    private $event;

    /**
     * @var string
     */
    private $season;

    /**
     * @var MalClient
     */
    private $jikan;

    /**
     * @var int
     */
    private $cotsChannelId;

    /**
     * @var int
     */
    private $roleId;

    /**
     * ValidateSubscriber constructor.
     *
     * @param string    $season
     * @param MalClient $jikan
     * @param int       $cotsChannelId
     * @param int       $roleId
     */
    public function __construct(
        string $season,
        MalClient $jikan,
        int $cotsChannelId,
        int $roleId
    ) {
        $this->season = $season;
        $this->jikan = $jikan;
        $this->cotsChannelId = $cotsChannelId;
        $this->roleId = $roleId;
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
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
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
        $nominationCount = count($nominations);
        try {
            if ($nominationCount <= 2) {
                throw new RuntimeException('Not enough nominees');
            }
            if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
                throw new RuntimeException('There is no clear winner');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->reply(':x: '.$e->getMessage());

            return;
        }
        /** @var TextChannelInterface $cotsChannel */
        $cotsChannel = $message->client->channels->get($this->cotsChannelId);
        $output = ['De huidige Character of the season ranking is'];
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

        $cotsChannel->send(implode(PHP_EOL, $output));
        $io->success('Displayed top 10');
        /** @var GuildChannelInterface $cotsGuildChannel */
        $cotsGuildChannel = $message->guild->channels->get($this->cotsChannelId);
        $cotsGuildChannel->overwritePermissions(
            $this->roleId,
            0,
            Channel::ROLE_SEND_MESSAGES,
            'Finished character of the season'
        );
        $nomination = $nominations[0];
        $cotsChannel->send(
            sprintf(
                ":trophy: Het character van %s is **%s**! van **%s**\n"
                ."Genomineerd door %s\nhttps://myanimelist.net/character/%s",
                $this->season,
                $nomination->getCharacter()->name,
                $nomination->getAnime()->getTitle(),
                $nomination->getAuthor(),
                $nomination->getCharacter()->getMalId()
            )
        );
        $io->success('Announced the winner');
    }
}
