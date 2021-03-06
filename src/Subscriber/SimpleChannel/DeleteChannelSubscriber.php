<?php

namespace App\Subscriber\SimpleChannel;

use App\Entity\Reaction;
use App\Event\ReactionAddedEvent;
use App\Message\SimpleJoinableChannelMessage;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Delete a channel by Reaction
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class DeleteChannelSubscriber implements EventSubscriberInterface
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
        if (!$event->isAdmin() || !$event->isBotMessage()) {
            return;
        }
        if ($reaction->emoji->name !== Reaction::DELETE) {
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
        /** @var TextChannel $channel */
        $channel = $reaction->message->guild->channels->get($channelMessage->getChannelId());

        // Delete
        /** @var TextChannel $tmpChannel */
        $tmpChannel = $reaction->message->guild->channels->get($channelMessage->getChannelId());
        $tmpChannel->delete('Remove joinable channel');
        $io->success('Simple joinable channel removed #'.$channel->name);
        $reaction->message->delete();
    }
}
