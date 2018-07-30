<?php

namespace App\Subscriber\SimpleChannel;

use App\Channel\Channel;
use App\Entity\Reaction;
use App\Event\ReactionAddedEvent;
use App\Message\SimpleJoinableChannelMessage;
use App\Util\Util;
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
        if ($reaction->emoji->name !== Reaction::JOIN || !$event->isBotMessage()) {
            return;
        }
        if (!SimpleJoinableChannelMessage::isJoinChannelMessage($reaction->message)) {
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
                    $channelMessage = new SimpleJoinableChannelMessage($reaction->message);
                    $channelMessage->updateWatchers($channel->id, $members);
                    $channel->send(
                        sprintf(
                            ':inbox_tray:  %s joined %s',
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
