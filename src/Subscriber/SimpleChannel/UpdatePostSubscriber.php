<?php

namespace App\Subscriber\SimpleChannel;

use App\Channel\Channel;
use App\Entity\Reaction;
use App\Event\ReactionAddedEvent;
use App\Message\SimpleJoinableChannelMessage;
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
        if ($reaction->emoji->name !== Reaction::REFRESH || !$event->isBotMessage()) {
            return;
        }
        if (!SimpleJoinableChannelMessage::isJoinChannelMessage($reaction->message)) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        // Load
        $channelMessage = new SimpleJoinableChannelMessage($reaction->message);
        if (!$reaction->message->editable) {
            $io->error('Message is not editable.');
        }
        $channelId = $channelMessage->getChannelId();
        $channel = $reaction->message->guild->channels->get($channelId);
        $channelMessage->updateWatchers($channelId, Channel::getUserCount($channel));
        $reaction->message->react(Reaction::JOIN);
        $reaction->message->react(Reaction::LEAVE);
        $reaction->remove($reaction->users->last());
        $io->success(sprintf('Updated %s simple joinable channel', $channel->name));
    }
}
