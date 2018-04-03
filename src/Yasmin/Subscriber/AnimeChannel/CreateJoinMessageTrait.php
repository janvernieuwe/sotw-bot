<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

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
        return sprintf(
            ":tv: **%s** \nchannel: %s\ndate: *%s* | episodes: %s | subs: **%s**\nmal: %s",
            $anime->title,
            Util::channelLink($channelId),
            $anime->aired_string,
            $anime->episodes ?: '?',
            $subs,
            $link
        );
    }
}
