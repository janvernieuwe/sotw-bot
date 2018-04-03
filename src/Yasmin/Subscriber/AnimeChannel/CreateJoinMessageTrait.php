<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Message\JoinableChannelMessage;
use App\Util\Util;
use Jikan\Model\Anime;

/**
 * Trait CreateJoinMessageTrait
 * @package App\Yasmin\Subscriber\AnimeChannel
 */
trait CreateJoinMessageTrait
{
    /**
     * @param Anime $anime
     * @param int $channelId
     * @param string $link
     * @param int $subs
     * @return string
     */
    public function generateJoinMessage(Anime $anime, int $channelId, string $link, int $subs = 0): string
    {
        return ':tv:';
        return sprintf(
            ":tv: **%s** \nchannel: %s | datum: %s | afleveringen: %s | kijkers: %s | mal: %s",
            $anime->title,
            Util::channelLink($channelId),
            $anime->aired_string,
            $anime->episodes ?: '?',
            $subs,
            $link
        );
    }

    /**
     * @param JoinableChannelMessage $message
     * @param int $subs
     * @return array
     */
    public function updateRichJoin(JoinableChannelMessage $message, int $subs = 0): array
    {
        preg_match('#\*\*(.*)\*\*#', $message->getAnimeTitle(), $title);
        $anime = new Anime();
        $anime->title = $title[1];
        $anime->episodes = $message->getFieldValue('afleveringen');
        $anime->aired_string = $message->getFieldValue('datum');
        $anime->image_url = $message->getAnimeImageUrl();
        preg_match('/c=(\d+)/', $message->getEmbeddedAnimeLink(), $channelid);
        $channelid = (int)$channelid[1];

        return $this->generateRichChannelMessage($anime, $channelid, $message->getEmbeddedAnimeLink(), $subs);
    }

    /**
     * @param Anime $anime
     * @param int $channelId
     * @param string $link
     * @param int $subs
     * @return array
     */
    public function generateRichChannelMessage(Anime $anime, int $channelId, string $link, int $subs = 0): array
    {
        return [
            'embed' => [
                'title'     => '**'.$anime->title.'**',
                'url'       => $link,
                'thumbnail' => ['url' => $anime->image_url],
                'fields'    => [
                    [
                        'name'   => 'datum',
                        'value'  => $anime->aired_string,
                        'inline' => true,
                    ],
                    [
                        'name'   => 'afleveringen',
                        'value'  => $anime->episodes,
                        'inline' => true,
                    ],
                    [
                        'name'   => 'channel',
                        'value'  => Util::channelLink($channelId),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'kijkers',
                        'value'  => $subs,
                        'inline' => true,
                    ],
                ],
            ],
        ];
    }
}
