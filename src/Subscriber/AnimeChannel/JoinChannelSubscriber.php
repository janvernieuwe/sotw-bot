<?php

namespace App\Subscriber\AnimeChannel;

use App\Channel\Channel;
use App\Entity\Reaction;
use App\Event\ReactionAddedEvent;
use App\Message\JoinableChannelMessage;
use App\Util\Util;
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

    public function onCommand(ReactionAddedEvent $event): void
    {
        $reaction = $event->getReaction();
        if ($reaction->emoji->name !== Reaction::JOIN || !$event->isBotMessage()) {
            return;
        }
        if (!JoinableChannelMessage::isJoinChannelMessage($reaction->message)) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        Channel::addUserFromReaction($reaction)
            ->then(
                function (int $members) use ($reaction, $io) {
                    $channel = Channel::getTextChannel($reaction->message);
                    $user = $reaction->users->last();
                    $channelMessage = new JoinableChannelMessage($reaction->message);
                    $anime = $this->mal->getAnime(new AnimeRequest($channelMessage->getAnimeId()));
                    $channelMessage->updateWatchers($anime, $channel->id, $members);
                    $channel->send(
                        sprintf(
                            ':inbox_tray:  %s kijkt nu mee naar %s',
                            Util::mention((int)$user->id),
                            Util::channelLink((int)$channel->id)
                        )
                    );
                    $io->success($user->username.' joined #'.$channel->name);
                }
            )
            ->otherwise(
                function (string $error) use ($io) {
                    $io->error($error);
                }
            )
            ->always(
                function () use ($reaction) {
                    $user = $reaction->users->last();
                    $reaction->remove($user);
                }
            );
    }
}
