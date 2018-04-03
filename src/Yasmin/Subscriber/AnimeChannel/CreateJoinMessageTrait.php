<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Util\Util;
use Jikan\Model\Anime;

trait CreateJoinMessageTrait
{
    /**
     * @param Anime $anime
     * @param int $channelId
     * @param string $link
     * @return string
     */
    public function generateJoinMessage(Anime $anime, int $channelId, string $link): string
    {
        return sprintf(
            ":tv: **%s** \nchannel: %s | date: *%s* | episodes: %s \nmal: %s",
            $anime->title,
            Util::channelLink($channelId),
            $anime->aired_string,
            $anime->episodes ?: '?',
            $link
        );
    }
}
