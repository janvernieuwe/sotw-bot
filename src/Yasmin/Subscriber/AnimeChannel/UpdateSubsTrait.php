<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

/**
 * Trait UpdateSubsTrait
 * @package App\Yasmin\Subscriber\AnimeChannel
 */
trait UpdateSubsTrait
{
    /**
     * @param string $message
     * @param int $subscribers
     * @return mixed
     */
    public function updateSubscribers(string $message, int $subscribers): string
    {
        return preg_replace('#kijkers: (\d+)#', 'kijkers: '.$subscribers, $message);
    }
}
