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
            ":tv: **%s** (*%s)\nChannel: %s\nAnime: %s",
            $anime->title,
            $anime->aired_string,
            Util::channelLink($channelId),
            $link
        );
    }
}
