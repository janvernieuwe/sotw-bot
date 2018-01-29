<?php

namespace App\Channel;

use App\Message\Message;
use GuzzleHttp\Command\Result;
use RestCord\DiscordClient;

/**
 * Class Channel
 * @package App\Channel
 */
class Channel
{
    public const ROLE_SEND_MESSAGES = 0x00000800;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var DiscordClient
     */
    private $discord;

    /**
     * @var bool
     */
    private $test = false;

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

    /**
     * @param string $message
     */
    public function message(string $message): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $message,
            ]
        );
    }

    /**
     * Deny a role permission on the channel
     * @param int $role
     * @param int $permission
     */
    public function deny(int $role, int $permission): void
    {
        $this->discord->channel->editChannelPermissions(
            [
                'channel.id'   => $this->channelId,
                'overwrite.id' => $role,
                'deny'         => $permission,
                'type'         => 'role',
            ]
        );
    }

    /**
     * @param int $role
     * @param int $permission
     */
    public function allow(int $role, int $permission): void
    {
        $this->discord->channel->editChannelPermissions(
            [
                'channel.id'   => $this->channelId,
                'overwrite.id' => $role,
                'allow'        => $permission,
                'type'         => 'role',
            ]
        );
    }

    /**
     * @param int $limit
     * @return Result|array
     */
    public function getMessages(int $limit = 10): Result
    {
        return $this->discord->channel->getChannelMessages(
            [
                'channel.id' => $this->channelId,
                'limit'      => $limit,
            ]
        );
    }
}
