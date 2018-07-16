<?php

namespace App\Subscriber\MangaChannel;

use App\Event\ReactionAddedEvent;
use App\Message\JoinableMangaChannelMessage;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Manga\MangaRequest;
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
     * DeleteChannelSubscriber constructor.
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
        if (!$reaction->message->editable) {
            $io->error('Message is not editable.');
        }
        $channelId = $channelMessage->getChannelId();
        $channel = $reaction->message->guild->channels->get($channelId);
        $subs = $channelMessage->getSubsciberCount($channel);
        $manga = $this->mal->getManga(new MangaRequest($channelMessage->getMangaId()));
        $channelMessage->updateWatchers($manga, $subs);
        $reaction->message->react(JoinableMangaChannelMessage::JOIN_REACTION);
        $reaction->message->react(JoinableMangaChannelMessage::LEAVE_REACTION);
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Updated %s manga channel', $manga->getTitle()));
    }
}
