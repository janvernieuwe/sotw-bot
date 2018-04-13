<?php

namespace App\Context;

use CharlotteDunois\Yasmin\Models\Message;
use Jikan\Model\Anime;

/**
 * Class CreateAnimeChannelContext
 * @package App\Context
 */
class CreateAnimeChannelContext
{
    /**
     * @var Anime
     */
    private $anime;

    /**
     * @var int
     */
    private $parent;

    /**
     * @var string
     */
    private $channelName;

    /**
     * @var int
     */
    private $everyoneRole;

    /**
     * @var Message
     */
    private $message;

    /**
     * CreateAnimeChannelContext constructor.
     *
     * @param Anime $anime
     * @param int $parent
     * @param string $channelName
     * @param int $everyoneRole
     * @param Message $message
     */
    public function __construct(
        int $parent,
        string $channelName,
        Anime $anime,
        int $everyoneRole,
        Message $message
    ) {
        $this->anime = $anime;
        $this->parent = $parent;
        $this->channelName = $channelName;
        $this->everyoneRole = $everyoneRole;
        $this->message = $message;
    }

    /**
     * @return Anime
     */
    public function getAnime(): Anime
    {
        return $this->anime;
    }

    /**
     * @return int
     */
    public function getParent(): int
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @return int
     */
    public function getEveryoneRole(): int
    {
        return $this->everyoneRole;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }
}
