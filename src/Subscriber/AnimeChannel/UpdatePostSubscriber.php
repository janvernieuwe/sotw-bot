<?php

namespace App\Subscriber\AnimeChannel;

use App\Event\ReactionAddedEvent;
use App\Message\JoinableChannelMessage;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Join a channel by Reaction
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class UpdatePostSubscriber implements EventSubscriberInterface
{
    /**
     * @var MalClient
     */
    private $mal;

    /**
     * UpdatePostSubscriber constructor.
     *
     * @param MalClient $mal
     */
    public function __construct(MalClient $mal)
    {
        $this->mal = $mal;
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
        if (!$event->isAdmin()) {
            return;
        }
        if ($reaction->emoji->name !== JoinableChannelMessage::RELOAD_REACTION || !$event->isBotMessage()) {
            return;
        }
        if (!JoinableChannelMessage::isJoinChannelMessage($reaction->message)) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        // Load
        $channelMessage = new JoinableChannelMessage($reaction->message);
        $anime = $this->mal->getAnime(new AnimeRequest($channelMessage->getAnimeId()));
        if (!$reaction->message->editable) {
            $io->error('Message is not editable.');
        }
        $channelId = $channelMessage->getChannelId();
        $channel = $reaction->message->guild->channels->get($channelId);
        $subs = $channelMessage->getSubsciberCount($channel);
        $channelMessage->updateWatchers($anime, $subs);
        $reaction->message->react(JoinableChannelMessage::JOIN_REACTION);
        $reaction->message->react(JoinableChannelMessage::LEAVE_REACTION);
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Updated %s anime channel', $anime->getTitle()));
    }
}
