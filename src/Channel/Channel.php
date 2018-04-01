<?php

namespace App\Channel;

use App\Message\Message;
use App\MyAnimeList\MyAnimeListClient;
use GuzzleHttp\Command\Result;
use RestCord\DiscordClient;
use RestCord\Model\Channel\Reaction;

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
     * @var MyAnimeListClient
     */
    protected $mal;

    /**
     * Channel constructor.
     * @param DiscordClient $discord
     * @param int $channelId
     * @param MyAnimeListClient $mal
     */
    public function __construct(DiscordClient $discord, int $channelId, MyAnimeListClient $mal)
    {
        $this->channelId = $channelId;
        $this->discord = $discord;
        $this->mal = $mal;
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
     * @param int $id
     */
    public function removeMessage(int $id): void
    {
        $this->discord->channel->deleteMessage(
            [
                'channel.id' => $this->channelId,
                'message.id' => $id,
            ]
        );
        sleep(1);
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
     * @return array
     */
    public function getManyMessages(int $limit = 1000): array
    {
        $messages = [];
        $lastId = null;
        for ($i = 0; $i < $limit; $i += 100) {
            $params = [
                'channel.id' => $this->channelId,
                'limit'      => 100,
            ];
            if (count($messages)) {
                $id = (int)end($messages)['id'];
                if ($id === $lastId) {
                    break;
                }
                $params['before'] = $lastId = $id;
            }
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $messages = array_merge($messages, $this->discord->channel->getChannelMessages($params)->toArray());
        }

        return $messages;
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

    /**
     * @param string $uri
     * @param string|null $content
     * @return \RestCord\Model\Channel\Message|array
     */
    public function embedImage(string $uri, string $content = null)
    {
        return $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $content,
                'embed'      => [
                    'image' => [
                        'url'    => $uri,
                        'height' => 128,
                        'width'  => 128,
                    ],
                ],
            ]
        );
    }

    /**
     * @param array $messages
     * @return array
     */
    public function sortByVotes(array $messages): array
    {
        uasort(
            $messages,
            function (Message $a, Message $b) {
                return $a->getVotes() < $b->getVotes();
            }
        );

        return array_values($messages);
    }

    /**
     * @param Message $message
     * @param string $emoji
     * @return Reaction[]
     */
    public function getReactions(Message $message, string $emoji): array
    {
        return $this->discord->channel->getReactions(
            [
                'channel.id' => $this->channelId,
                'message.id' => $message->getMessageId(),
                'emoji'      => $emoji,
            ]
        )->toArray();
    }

    /**
     * Get the channel id
     * @return int
     */
    public function getChannelId(): int
    {
        return $this->channelId;
    }

    /**
     * @return MyAnimeListClient
     */
    public function getMal(): MyAnimeListClient
    {
        return $this->mal;
    }
}
