<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Message\JoinableChannelMessage;
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
    use CreateJoinMessageTrait;

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
        $io = $event->getIo();
        if (!$event->isAdmin()) {
            return;
        }
        if ($reaction->emoji->name !== JoinableChannelMessage::RELOAD_REACTION || !$event->isBotMessage()) {
            return;
        }
        if (!JoinableChannelMessage::isJoinableChannel($reaction->message->content)) {
            $io->writeln('Not a joinable channel reaction');

            return;
        }
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        // Load
        $channelMessage = new JoinableChannelMessage($reaction->message);
        $anime = $this->mal->loadAnime($channelMessage->getAnimeId());
        if (!$reaction->message->editable) {
            $io->error('Message is not editable.');
        }
        $channel = $reaction->message->guild->channels->get($channelMessage->getChannelId());
        $subs = $channelMessage->getSubsciberCount($channel);
        $reaction->message->edit(
            $this->generateJoinMessage(
                $anime,
                $channelMessage->getChannelId(),
                $channelMessage->getAnimeLink(),
                $subs
            )
        );
        $reaction->message->react(JoinableChannelMessage::JOIN_REACTION);
        $reaction->message->react(JoinableChannelMessage::LEAVE_REACTION);
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Updated %s anime channel', $anime->title));
    }
}
