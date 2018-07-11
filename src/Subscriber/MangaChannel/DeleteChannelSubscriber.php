<?php

namespace App\Subscriber\MangaChannel;

use App\Event\ReactionAddedEvent;
use App\Message\JoinableMangaChannelMessage;
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
        if ($reaction->emoji->name !== JoinableMangaChannelMessage::DELETE_REACTION) {
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
        /** @var TextChannel $channel */
        $channel = $reaction->message->guild->channels->get($channelMessage->getChannelId());

        // Delete
        /** @var TextChannel $tmpChannel */
        $tmpChannel = $reaction->message->guild->channels->get($channelMessage->getChannelId());
        $tmpChannel->delete('Remove joinable channel');
        $io->success('Manga channel removed #'.$channel->name);
        $reaction->message->delete();
    }
}
