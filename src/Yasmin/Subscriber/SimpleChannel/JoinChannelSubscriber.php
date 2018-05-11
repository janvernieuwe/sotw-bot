<?php

namespace App\Yasmin\Subscriber\SimpleChannel;

use App\Message\SimpleJoinableChannelMessage;
use App\Yasmin\Event\ReactionAddedEvent;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Models\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Join a channel by Reaction
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class JoinChannelSubscriber implements EventSubscriberInterface
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
        if ($reaction->emoji->name !== SimpleJoinableChannelMessage::JOIN_REACTION || !$event->isBotMessage()) {
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
        /** @var User $user */
        $user = $reaction->users->last();
        /** @var GuildMember $member */
        $member = $reaction->message->guild->members->get($user->id);
        /** @var TextChannel $channel */
        $channel = $reaction->message->guild->channels->get($channelMessage->getChannelId());
        if ($channelMessage->hasAccess($user->id)) {
            $io->writeln(sprintf('User %s already has joined %s', $user->username, $channel->name));
            $reaction->remove($reaction->users->last());

            return;
        }
        // Join
        $channelMessage->addUser($member);
        $reaction->remove($reaction->users->last());
        $io->success($user->username.' joined #'.$channel->name);
    }
}
