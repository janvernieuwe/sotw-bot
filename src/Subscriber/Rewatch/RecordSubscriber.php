<?php

namespace App\Subscriber\Rewatch;

use App\Entity\RewatchWinner;
use App\Event\ReactionAddedEvent;
use App\Exception\RuntimeException;
use App\Message\RewatchNomination;
use Doctrine\ORM\EntityManagerInterface;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Record a rewatch to the database
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class RecordSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var int
     */
    private $rewatchChannelId;

    /**
     * @var MalClient
     */
    private $jikan;

    /**
     * AutoValidateSubscriber constructor.
     *
     * @param int                    $rewatchChannelId
     * @param EntityManagerInterface $doctrine
     * @param MalClient              $jikan
     */
    public function __construct(int $rewatchChannelId, EntityManagerInterface $doctrine, MalClient $jikan)
    {
        $this->doctrine = $doctrine;
        $this->rewatchChannelId = $rewatchChannelId;
        $this->jikan = $jikan;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [ReactionAddedEvent::NAME => 'onCommand'];
    }

    /**
     * @param ReactionAddedEvent $event
     */
    public function onCommand(ReactionAddedEvent $event): void
    {
        $reaction = $event->getReaction();
        /** @noinspection PhpUndefinedFieldInspection */
        if ($reaction->emoji->name !== '⏺' || !$event->isAdmin()) {
            return;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$reaction->message->channel->id !== $this->rewatchChannelId) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        try {
            if (!RewatchNomination::isContender($reaction->message->content)) {
                throw new RuntimeException('Not a contender');
            }
            $nomination = RewatchNomination::fromMessage($reaction->message);
            try {
                $anime = $this->jikan->getAnime(new AnimeRequest($nomination->getAnimeId()));
            } catch (\Exception $e) {
                throw new RuntimeException('Invalid anime link');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return;
        }

        $watch = new RewatchWinner();
        $watch
            ->setTitle($anime->getTitle())
            ->setEpisodes($anime->getEpisodes())
            ->setAired($anime->getAired())
            ->setVotes($reaction->message->reactions->get('🔼')->count)
            ->setCreated(new \DateTime())
            ->setAnimeId($anime->getMalId())
            ->setMemberId($reaction->message->author->id)
            ->setDisplayName($reaction->message->author->username);

        $this->doctrine->persist($watch);
        $this->doctrine->flush();
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Recorded %s', $anime->getTitle()));
    }
}
