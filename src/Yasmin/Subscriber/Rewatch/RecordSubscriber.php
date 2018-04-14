<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Entity\RewatchWinner;
use App\Exception\RuntimeException;
use App\Message\RewatchNomination;
use App\Yasmin\Event\ReactionAddedEvent;
use CharlotteDunois\Yasmin\Models\MessageReaction;
use Doctrine\ORM\EntityManagerInterface;
use RestCord\Model\Channel\Reaction;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Record a rewatch to the database
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class RecordSubscriber implements EventSubscriberInterface
{
    /**
     * @var RewatchChannel
     */
    private $rewatch;
    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * AutoValidateSubscriber constructor.
     * @param RewatchChannel $rewatch
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(RewatchChannel $rewatch, EntityManagerInterface $doctrine)
    {
        $this->rewatch = $rewatch;
        $this->doctrine = $doctrine;
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
        if ((int)$reaction->message->channel->id !== $this->rewatch->getChannelId()) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        try {
            if (!RewatchNomination::isContender($reaction->message->content)) {
                throw new RuntimeException('Not a contender');
            }
            $nomination = RewatchNomination::fromYasmin($reaction->message);
            try {
                $anime = $this->rewatch->getMal()->loadAnime($nomination->getAnimeId());
            } catch (\Exception $e) {
                throw new RuntimeException('Invalid anime link');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return;
        }

        $watch = new RewatchWinner();
        $watch
            ->setTitle($anime->title)
            ->setEpisodes($anime->episodes)
            ->setAired($anime->aired_string)
            ->setVotes($reaction->message->reactions->get('🔼')->count)
            ->setCreated(new \DateTime())
            ->setAnimeId($anime->mal_id)
            ->setMemberId($reaction->message->author->id)
            ->setDisplayName($reaction->message->author->username);

        $this->doctrine->persist($watch);
        $this->doctrine->flush();
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Recorded %s', $anime->title));
    }
}
