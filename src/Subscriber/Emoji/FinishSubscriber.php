<?php

namespace App\Subscriber\Emoji;

use App\Event\MessageReceivedEvent;
use App\Message\YasminEmojiNomination;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\Emoji;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Utils\Collection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function React\Promise\all;

/**
 * Display the current bikkel ranking
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    private const CMD = '!haamc emoji finish';

    /**
     * @var TextChannel
     */
    public static $channel;

    /**
     * @var Message
     */
    public static $message;

    /**
     * @var YasminEmojiNomination[]
     */
    public static $winners = [];

    /**
     * @var SymfonyStyle
     */
    private static $io;

    /**
     * @var int
     */
    private $channelId;

    /**
     * NominationSubscriber constructor.
     *
     * @param int $emojiChannelId
     *
     * @internal param int $channelId
     */
    public function __construct(int $emojiChannelId)
    {
        $this->channelId = $emojiChannelId;
    }

    /**
     *
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        self::$message = $message = $event->getMessage();
        if ($message->content !== self::CMD || !$event->isAdmin()) {
            return;
        }
        $event->stopPropagation();
        self::$io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');

        /** @var TextChannel $channel */
        self::$channel = $channel = $message->guild->channels->get($this->channelId);
        $channel->fetchMessages(['limit' => 100])->done(
            function (Collection $result) {
                $nominations = $this->filter($result->all());
                self::$io->writeln(sprintf('#nominations %s', \count($nominations)));
                self::$winners = array_slice($nominations, 0, 50);
                $this->removeLosers(array_slice($nominations, 49));
            }
        );
    }

    /**
     * @param array|Message[] $messages
     *
     * @return YasminEmojiNomination[]
     */
    private function filter(array $messages): array
    {
        $valid = [];
        foreach ($messages as $message) {
            $message = new YasminEmojiNomination($message);
            if (!$message->isValid()) {
                continue;
            }
            $valid[] = $message;
        }
        usort(
            $valid,
            function (YasminEmojiNomination $a, YasminEmojiNomination $b) {
                return $a->getVotes() < $b->getVotes();
            }
        );

        return $valid;
    }

    /**
     * @param array|YasminEmojiNomination[] $losers
     */
    private function removeLosers(array $losers)
    {
        $promises = [];
        foreach ($losers as $loser) {
            if (!$loser->isOnServer()) {
                continue;
            }
            self::$io->writeln(sprintf('Loser: %s %s', $loser->getContent(), $loser->getVotes()));
            self::$message->channel->send(
                sprintf(':put_litter_in_its_place:  %s', $loser->getContent())
            );
            /** @var Emoji $emoji */
            $emoji = self::$message->guild->emojis->keyBy('name')->get($loser->getEmojiName());
            $promises[] = $promise = $emoji->delete();
            $promise->done(
                function () use ($loser) {
                    self::$io->success(sprintf('Emoji %s removed', $loser->getEmojiName()));
                }
            );
        }
        all($promises)->then(
            function () {
                $this->addWinners();
            }
        );
    }

    private function addWinners()
    {
        foreach (self::$winners as $winner) {
            if ($winner->isOnServer()) {
                continue;
            }
            self::$io->writeln(sprintf('Winner: %s %s', $winner->getContent(), $winner->getVotes()));
            //self::$message->channel->send($winner->getUrl());

            self::$message->guild->createEmoji($winner->getUrl(), $winner->getEmojiName())->done(
                function (Emoji $emoji) {
                    self::$message->channel->send(':new: '.Util::emojiToString($emoji))->done(
                        function (Message $emojiPost) use ($emoji) {
                            $emojiPost->react(Util::emojiToString($emoji));
                            self::$io->success(sprintf('Emoji %s added', $emoji->name));
                        }
                    );
                }
            );
        }
    }
}
