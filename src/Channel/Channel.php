<?php

namespace App\Channel;

use App\Message\Message;
use RestCord\DiscordClient;

/**
 * Class Channel
 * @package App\Channel
 */
class Channel
{
    /**
     * @var bool
     */
    private $test = false;

    /**
     * @var int
     */
    protected $channelId;

    /**
     * @var DiscordClient
     */
    protected $discord;

    /**
     * Channel constructor.
     * @param int $channelId
     * @param DiscordClient $discord
     */
    public function __construct(DiscordClient $discord, int $channelId)
    {
        $this->channelId = $channelId;
        $this->discord = $discord;
    }

    /**
     * @param bool $test
     */
    public function setTest(bool $test): void
    {
        $this->test = $test;
    }

    /**
     * @param Message $message
     * @param string $emoji
     */
    public function addReaction(Message $message, string $emoji): void
    {
        if ($message->hasReaction($emoji)) {
            return;
        }
        $this->discord->channel->createReaction(
            [
                'channel.id' => $this->channelId,
                'message.id' => $message->getMessageId(),
                'emoji'      => $emoji,
            ]
        );
        if (!$this->test) {
            sleep(1);
        }
    }

    /**
     * @param Message $message
     * @param string $emoji
     */
    public function removeReaction(Message $message, string $emoji): void
    {
        if (!$message->hasReaction($emoji)) {
            return;
        }
        $this->discord->channel->deleteOwnReaction(
            [
                'channel.id' => $this->channelId,
                'message.id' => $message->getMessageId(),
                'emoji'      => $emoji,
            ]
        );
        if (!$this->test) {
            sleep(1);
        }
    }
}
