<?php

namespace App\Message;

use App\Channel\Channel;
use App\Exception\InvalidChannelException;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Jikan\Model\Manga\Manga;

/**
 * Class JoinableMangaChannelMessage
 *
 * @package App\Message
 */
class JoinableMangaChannelMessage
{
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
        return Channel::getFieldValue($this->message, $key);
    }

    /**
     * @return int|null
     */
    public function getMangaId(): ?int
    {
        if (preg_match('#https?://myanimelist.net/manga/(\d+)#', $this->getMangaLink(), $channel)) {
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
     * @return TextChannel
     * @throws InvalidChannelException
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
     * @param Manga $manga
     * @param int   $channelId
     * @param int   $subs
     */
    public function updateWatchers(Manga $manga, int $channelId, int $subs = 0): void
    {
        $embed = self::generateRichChannelMessage($manga, $channelId, $this->getEmbeddedMangaLink(), $subs);
        $this->message->edit(self::TEXT_MESSAGE, $embed);
    }

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
                    'name'     => $manga->getTitle(),
                    'icon_url' => self::AUTHOR_IMG_URL,
                    'url'      => $link,
                ],
                'url'       => $link,
                'thumbnail' => ['url' => $manga->getImageUrl()],
                'footer'    => [
                    'text' => 'Druk op de reactions om te joinen / leaven',
                ],
                'fields'    => [
                    [
                        'name'   => 'datum',
                        'value'  => (string)$manga->getPublished(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'rank',
                        'value'  => $manga->getRank(),
                        'inline' => true,
                    ],
                    [
                        'name'   => Channel::CHANNEL_KEY,
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
}
