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
 * Leave a channel by Reaction
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class LeaveChannelSubscriber implements EventSubscriberInterface
{
    use AccessCheckingTrait;
    use UpdateSubsTrait;
    use CreateJoinMessageTrait;

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

        if ($reaction->emoji->name !== JoinableChannelMessage::LEAVE_REACTION || !$event->isBotMessage()) {
            return;
        }
        if (!JoinableChannelMessage::isJoinChannelMessage($reaction->message)) {
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

        if (!$this->hasAccess($channel, $user->id)) {
            $io->writeln(sprintf('User %s already has left %s', $user->username, $channel->name));
            $reaction->remove($reaction->users->last());


            return;
        }

        // Leave
        /** @var  $override */
        $channel->overwritePermissions(
            $member->id,
            0,
            Channel::ROLE_VIEW_MESSAGES,
            'User left the channel'
        );
        $count = $channelMessage->getSubsciberCount($channel) - 1;
        $reaction->message->edit(
            ':tv:',
            $this->updateRichJoin($channelMessage, $count)
        );
        $channel->send(
            sprintf(
                ':outbox_tray: %s kijkt nu niet meer mee naar %s',
                Util::mention((int)$member->id),
                Util::channelLink($channelMessage->getChannelId())
            )
        );
        $reaction->remove($reaction->users->last());
        $io->success($user->username.' left #'.$channel->name);
    }
}
