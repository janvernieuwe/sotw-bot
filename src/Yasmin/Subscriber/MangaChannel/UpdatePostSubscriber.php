<?php

namespace App\Yasmin\Subscriber\MangaChannel;

use App\Message\JoinableMangaChannelMessage;
use App\MyAnimeList\MyAnimeListClient;
use App\Yasmin\Event\ReactionAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Join a channel by Reaction
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class UpdatePostSubscriber implements EventSubscriberInterface
{
    /**
     * @var MyAnimeListClient
     */
    private $mal;

    /**
     * UpdatePostSubscriber constructor.
     * @param MyAnimeListClient $mal
     */
    public function __construct(MyAnimeListClient $mal)
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
        if ($reaction->emoji->name !== JoinableMangaChannelMessage::RELOAD_REACTION || !$event->isBotMessage()) {
            return;
        }
        if (!JoinableMangaChannelMessage::isJoinChannelMessage($reaction->message)) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        // Load
        $channelMessage = new JoinableMangaChannelMessage($reaction->message);
        $anime = $this->mal->loadAnime($channelMessage->getAnimeId());
        if (!$reaction->message->editable) {
            $io->error('Message is not editable.');
        }
        $channelId = $channelMessage->getChannelId();
        $channel = $reaction->message->guild->channels->get($channelId);
        $subs = $channelMessage->getSubsciberCount($channel);
        $channelMessage->updateWatchers($subs);
        $reaction->message->react(JoinableMangaChannelMessage::JOIN_REACTION);
        $reaction->message->react(JoinableMangaChannelMessage::LEAVE_REACTION);
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Updated %s manga channel', $anime->title));
    }
}
