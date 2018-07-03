<?php

namespace App\Message;

use App\Channel\Channel;
use App\Exception\InvalidChannelException;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Jikan\Model\Manga;

/**
 * Class JoinableMangaChannelMessage
 *
 * @package App\Message
 */
class JoinableMangaChannelMessage
{
    public const CHANNEL_REGXP = '/(c=)(\d+)/';
    public const JOIN_REACTION = '▶';
    public const LEAVE_REACTION = '⏹';
    public const DELETE_REACTION = '🚮';
    public const RELOAD_REACTION = '🔁';
    public const TEXT_MESSAGE = '';
    private const AUTHOR_IMG_URL = 'https://i.imgur.com/pcdrHvS.png';

    /**
     * @var \CharlotteDunois\Yasmin\Models\Message
     */
    private $message;

    /**
     * JoinableChannelMessage constructor.
     *
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     */
    public function __construct(\CharlotteDunois\Yasmin\Models\Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public static function isJoinableChannel(string $content): bool
    {
        return preg_match(self::CHANNEL_REGXP, $content);
    }

    /**
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     *
     * @return bool
     */
    public static function isJoinChannelMessage(\CharlotteDunois\Yasmin\Models\Message $message): bool
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $message = new self($message);

        return $message->getFieldValue('lezers') !== null;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getFieldValue(string $key)
    {
        if (!count($this->message->embeds)) {
            return null;
        }
        $data = array_filter(
            $this->message->embeds[0]->fields,
            function (array $data) use ($key) {
                return $data['name'] === $key;
            }
        );
        if (!count($data)) {
            return null;
        }
        $data = array_values($data);

        return $data[0]['value'];
    }

    /**
     * @return int|null
     */
    public function getMangaId(): ?int
    {
        if (preg_match('#https?://myanimelist.net/anime/(\d+)#', $this->getMangaLink(), $channel)) {
            return (int)$channel[1];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getMangaLink(): ?string
    {
        return $this->message->embeds[0]->url;
    }

    /**
     * @param GuildMember $member
     *
     * @throws InvalidChannelException
     * @throws InvalidChannelException
     */
    public function addUser(GuildMember $member): void
    {
        // No double joins
        if ($this->hasAccess($member->id)) {
            return;
        }
        // Join channel
        $channel = $this->getChannelFromMessage();
        $channel->overwritePermissions(
            $member->id,
            Channel::ROLE_VIEW_MESSAGES,
            0,
            'User joined the channel'
        );
        // Update the member counf
        $count = $this->getSubsciberCount($channel) + 1;
        $this->updateWatchers($count);
        // Announce join
        $joinMessage = sprintf(
            ':inbox_tray:  %s leest nu ook %s',
            Util::mention((int)$member->id),
            Util::channelLink((int)$channel->id)
        );
        $channel->send($joinMessage);
    }

    /**
     * @param int $memberid
     *
     * @return bool
     * @throws InvalidChannelException
     * @throws InvalidChannelException
     */
    public function hasAccess(int $memberid): bool
    {
        $permissions = $this->getChannelFromMessage()->permissionOverwrites->all();
        $view = array_filter(
            $permissions,
            function (PermissionOverwrite $o) use ($memberid) {
                return $o->allow->bitfield === Channel::ROLE_VIEW_MESSAGES
                    && $memberid === (int)$o->id
                    && $o->type === 'member';
            }
        );

        return count($view) > 0;
    }

    /**
     * @return TextChannel
     * @throws InvalidChannelException
     */
    public function getChannelFromMessage(): TextChannel
    {
        $channel = $this->message->guild->channels->get($this->getChannelId());
        if ($channel === null) {
            throw new InvalidChannelException('Channel not found');
        }

        return $channel;
    }

    /**
     * @return int|null
     */
    public function getChannelId(): ?int
    {
        if (preg_match(self::CHANNEL_REGXP, $this->getMangaLink(), $channel)) {
            return (int)$channel[2];
        }

        return null;
    }

    /**
     * @param TextChannel $channel
     *
     * @return int
     */
    public function getSubsciberCount(TextChannel $channel): int
    {
        return count($this->getSubscribers($channel)) - 1;
    }

    /**
     * @param TextChannel $channel
     *
     * @return array
     */
    public function getSubscribers(TextChannel $channel): array
    {
        return array_filter(
            $channel->permissionOverwrites->all(),
            function (PermissionOverwrite $o) {
                return $o->allow->bitfield === Channel::ROLE_VIEW_MESSAGES;
            }
        );
    }

    /**
     * @param int $subs
     */
    public function updateWatchers(int $subs = 0): void
    {
        $manga = new Manga();
        $manga->title = $this->getMangaTitle();
        $manga->rank = $this->getFieldValue('rank');
        $manga->published_string = $this->getFieldValue('datum');
        $manga->image_url = $this->getMangaImageUrl();
        preg_match('/c=(\d+)/', $this->getEmbeddedMangaLink(), $channelid);
        $channelid = (int)$channelid[1];

        $embed = self::generateRichChannelMessage($manga, $channelid, $this->getEmbeddedMangaLink(), $subs);
        $this->message->edit(self::TEXT_MESSAGE, $embed);
    }

    /**
     * @return string
     */
    public function getMangaTitle(): string
    {
        return $this->message->embeds[0]->title ?? $this->message->embeds[0]->author['name'];
    }

    /**
     * @return string
     */
    public function getMangaImageUrl(): string
    {
        return $this->message->embeds[0]->thumbnail['url'];
    }/** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @return string
     */
    public function getEmbeddedMangaLink(): string
    {
        return $this->message->embeds[0]->url;
    }

    /**
     * @param Manga  $manga
     * @param int    $channelId
     * @param string $link
     * @param int    $subs
     *
     * @return array
     */
    public static function generateRichChannelMessage(Manga $manga, int $channelId, string $link, int $subs = 0): array
    {
        return [
            'embed' => [
                'author'    => [
                    'name'     => $manga->title,
                    'icon_url' => self::AUTHOR_IMG_URL,
                    'url'      => $link,
                ],
                'url'       => $link,
                'thumbnail' => ['url' => $manga->image_url],
                'footer'    => [
                    'text' => 'Druk op de reactions om te joinen / leaven',
                ],
                'fields'    => [
                    [
                        'name'   => 'datum',
                        'value'  => $manga->published_string,
                        'inline' => true,
                    ],
                    [
                        'name'   => 'rank',
                        'value'  => $manga->rank,
                        'inline' => true,
                    ],
                    [
                        'name'   => 'channel',
                        'value'  => Util::channelLink($channelId),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'lezers',
                        'value'  => (string)$subs,
                        'inline' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param GuildMember $member
     *
     * @throws InvalidChannelException
     * @throws InvalidChannelException
     */
    public function removeUser(GuildMember $member): void
    {
        // No double joins
        if (!$this->hasAccess($member->id)) {
            return;
        }
        // Remove member
        $channel = $this->getChannelFromMessage();
        $channel->overwritePermissions(
            $member->id,
            0,
            Channel::ROLE_VIEW_MESSAGES,
            'User left the channel'
        );
        // Update member count
        $count = $this->getSubsciberCount($channel) - 1;
        $this->updateWatchers($count);
        // Announce leave
        $channel->send(
            sprintf(
                ':outbox_tray: %s leest nu geen %s meer',
                Util::mention((int)$member->id),
                Util::channelLink($this->getChannelId())
            )
        );
    }

    /**
     * @return int
     */
    public function getWatchers(): int
    {
        return (int)$this->getFieldValue('lezers');
    }
}
