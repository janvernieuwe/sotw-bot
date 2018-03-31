<?php

namespace App\Channel;

use App\Message\Message;
use App\Util\Util;
use GuzzleHttp\Command\Result;
use Jikan\Jikan;
use Jikan\Model\Anime;
use Jikan\Model\Character;
use RestCord\DiscordClient;
use RestCord\Model\Channel\Reaction;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Class Channel
 * @package App\Channel
 */
class Channel
{
    public const ROLE_SEND_MESSAGES = 0x00000800;

    /**
     * @var AdapterInterface
     */
    protected $cache;

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
     * @var Jikan
     */
    protected $jikan;

    /**
     * Channel constructor.
     * @param DiscordClient $discord
     * @param int $channelId
     * @param AdapterInterface $cache
     * @param Jikan $jikan
     */
    public function __construct(DiscordClient $discord, int $channelId, AdapterInterface $cache, Jikan $jikan)
    {
        $this->channelId = $channelId;
        $this->discord = $discord;
        $this->cache = $cache;
        $this->jikan = $jikan;
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
     * @param int $id
     * @return Anime
     */
    public function loadAnime(int $id): Anime
    {
        $key = 'jikan_anime_'.$id;
        if (!$this->cache->hasItem($key)) {
            /** @var Anime $character */
            $anime = Util::instantiate(Anime::class, $this->jikan->Anime($id)->response);
            $item = $this->cache->getItem($key);
            $item->set($anime);
            $item->expiresAfter(strtotime('+7 day'));
            $this->cache->save($item);
        } else {
            $anime = $this->cache->getItem($key)->get();
        }

        return $anime;
    }

    /**
     * @param int $id
     * @return Character
     */
    public function loadCharacter(int $id): Character
    {
        $key = 'jikan_character_'.$id;
        if (!$this->cache->hasItem($key)) {
            /** @var Character $character */
            $character = Util::instantiate(Character::class, $this->jikan->Character($id)->response);
            $item = $this->cache->getItem($key);
            $item->set($character);
            $item->expiresAfter(strtotime('+7 day'));
            $this->cache->save($item);
        } else {
            $character = $this->cache->getItem($key)->get();
        }

        return $character;
    }
}
