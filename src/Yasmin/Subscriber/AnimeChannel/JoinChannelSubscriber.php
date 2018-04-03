<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Channel\Channel;
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
    use UpdateSubsTrait;
    use AccessCheckingTrait;

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

        if ($this->hasAccess($channel, $user->id)) {
            $io->writeln(sprintf('User %s already has joined %s', $user->username, $channel->name));
            $reaction->remove($reaction->users->last());

            return;
        }

        // Join
        $channel->overwritePermissions(
            $member->id,
            Channel::ROLE_VIEW_MESSAGES,
            0,
            'User joined the channel'
        );
        $count = $channelMessage->getSubsciberCount($channel) + 1;
        $reaction->message->edit($this->updateSubscribers($reaction->message, $count));
        $joinMessage = sprintf(
            ':inbox_tray:  %s kijkt nu mee naar %s',
            Util::mention((int)$user->id),
            Util::channelLink((int)$channel->id)
        );
        $channel->send($joinMessage);

        $reaction->remove($reaction->users->last());
        $io->success($user->username.' joined #'.$channel->name);
    }
}
