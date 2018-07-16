<?php

namespace App\Subscriber\MangaChannel;

use App\Event\ReactionAddedEvent;
use App\Message\JoinableMangaChannelMessage;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Models\User;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Manga\MangaRequest;
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
        if ($reaction->emoji->name !== JoinableMangaChannelMessage::JOIN_REACTION || !$event->isBotMessage()) {
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
        $mangaId = $channelMessage->getMangaId();
        $manga = $this->mal->getManga(new MangaRequest($mangaId));
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
        $channelMessage->addUser($manga, $member);
        $reaction->remove($reaction->users->last());
        $io->success($user->username.' joined #'.$channel->name);
    }
}
