<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Message\JoinableChannelMessage;
use App\Util\Util;
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
        $io = $event->getIo();
        if ($reaction->emoji->name !== JoinableChannelMessage::JOIN_REACTION || !$event->isBotMessage()) {
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
        /** @var User $user */
        $user = $reaction->users->last();
        /** @var GuildMember $member */
        $member = $reaction->message->guild->members->get($user->id);
        /** @var TextChannel $channel */
        $channel = $reaction->message->guild->channels->get($channelMessage->getChannelId());
        $roleId = $channelMessage->getRoleId();

        // Join
        if (!$member->roles->has($roleId)) {
            $member->addRole($roleId, 'User joined channel');
            $joinMessage = sprintf(
                ':arrow_forward:  %s kijkt nu mee naar %s',
                Util::mention((int)$user->id),
                Util::channelLink((int)$channel->id)
            );
            $channel->send($joinMessage);
        }
        $reaction->remove($reaction->users->last());
        $io->success($user->username.' joined #'.$channel->name);
    }
}
