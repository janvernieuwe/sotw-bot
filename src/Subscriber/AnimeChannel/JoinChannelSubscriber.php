<?php

namespace App\Subscriber\AnimeChannel;

use App\Event\ReactionAddedEvent;
use App\Message\JoinableChannelMessage;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Models\User;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Join a channel by Reaction
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class JoinChannelSubscriber implements EventSubscriberInterface
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
     *
     * @throws \App\Exception\InvalidChannelException
     */
    public function onCommand(ReactionAddedEvent $event): void
    {
        $reaction = $event->getReaction();
        if ($reaction->emoji->name !== JoinableChannelMessage::JOIN_REACTION || !$event->isBotMessage()) {
            return;
        }
        if (!JoinableChannelMessage::isJoinChannelMessage($reaction->message)) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        // Load
        $channelMessage = new JoinableChannelMessage($reaction->message);
        $anime = $this->mal->getAnime(new AnimeRequest($channelMessage->getAnimeId()));
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
        $channelMessage->addUser($anime, $member);
        $reaction->remove($reaction->users->last());
        $io->success($user->username.' joined #'.$channel->name);
    }
}
