<?php

namespace App\Message;

use App\Channel\Channel;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Jikan\Model\Anime\Anime;

/**
 * Class JoinableChannelMessage
 *
 * @package App\Message
 */
class JoinableChannelMessage
{
    public const TEXT_MESSAGE = '';
    public const AUTHOR_IMG_URL = 'https://i.imgur.com/pcdrHvS.png';

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
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     *
     * @return bool
     */
    public static function isJoinChannelMessage(\CharlotteDunois\Yasmin\Models\Message $message): bool
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $message = new self($message);

        return $message->getFieldValue('kijkers') !== null;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getFieldValue(string $key)
    {
        return Channel::getFieldValue($this->message, $key);
    }

    /**
     * @return int|null
     */
    public function getAnimeId(): ?int
    {
        if (preg_match('#https?://myanimelist.net/anime/(\d+)#', $this->getAnimeLink(), $channel)) {
            return (int)$channel[1];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getAnimeLink(): ?string
    {
        return $this->message->embeds[0]->url;
    }

    /**
     * @return TextChannel
     */
    public function getChannelFromMessage(): TextChannel
    {
        return Channel::getTextChannel($this->message);
    }

    /**
     * @return int|null
     */
    public function getChannelId(): ?int
    {
        return Channel::getChannelId($this->message);
    }

    /**
     * @param TextChannel $channel
     *
     * @return int
     */
    public function getSubsciberCount(TextChannel $channel): int
    {
        return Channel::getUserCount($channel);
    }

    /**
     * @param Anime $anime
     * @param int   $channelId
     * @param int   $subs
     */
    public function updateWatchers(Anime $anime, int $channelId, int $subs = 0): void
    {
        $embed = self::generateRichChannelMessage($anime, $channelId, $this->getEmbeddedAnimeLink(), $subs);
        $this->message->edit(self::TEXT_MESSAGE, $embed);
    }

    /**
     * @return string
     */
    public function getEmbeddedAnimeLink(): string
    {
        return $this->message->embeds[0]->url;
    }

    /**
     * @param Anime  $anime
     * @param int    $channelId
     * @param string $link
     * @param int    $subs
     *
     * @return array
     */
    public static function generateRichChannelMessage(Anime $anime, int $channelId, string $link, int $subs = 0): array
    {
        return [
            'embed' => [
                'author'    => [
                    'name'     => $anime->getTitle(),
                    'icon_url' => self::AUTHOR_IMG_URL,
                    'url'      => $link,
                ],
                'url'       => $link,
                'thumbnail' => ['url' => $anime->getImageUrl()],
                'footer'    => [
                    'text' => 'Druk op de reactions om te joinen / leaven',
                ],
                'fields'    => [
                    [
                        'name'   => 'studio',
                        'value'  => implode(', ', $anime->getStudios()),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'datum',
                        'value'  => (string)$anime->getAired(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'genres',
                        'value'  => implode(', ', $anime->getGenres()),
                        'inline' => false,
                    ],
                    [
                        'name'   => Channel::CHANNEL_KEY,
                        'value'  => Util::channelLink($channelId),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'kijkers',
                        'value'  => (string)$subs,
                        'inline' => true,
                    ],
                ],
            ],
        ];
    }/** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @return string
     */
    public function getAnimeTitle(): string
    {
        return $this->message->embeds[0]->title ?? $this->message->embeds[0]->author['name'];
    }

    /**
     * @return string
     */
    public function getAnimeImageUrl(): string
    {
        return $this->message->embeds[0]->thumbnail['url'];
    }

    /**
     * @return int
     */
    public function getWatchers(): int
    {
        return (int)$this->getFieldValue('kijkers');
    }
}
