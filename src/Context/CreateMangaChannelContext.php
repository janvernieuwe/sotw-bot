<?php

namespace App\Context;

use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Guild;
use Jikan\Model\Manga\Manga;

/**
 * Class CreateMangaChannelContext
 *
 * @package App\Context
 */
class CreateMangaChannelContext
{
    /**
     * @var Manga
     */
    private $manga;

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
     * @var Guild
     */
    private $guild;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var TextChannelInterface
     */
    private $channel;

    /**
     * CreateAnimeChannelContext constructor.
     *
     * @param Manga                $manga
     * @param int                  $parent
     * @param string               $channelName
     * @param int                  $everyoneRole
     * @param Guild                $guild
     * @param Client               $client
     * @param TextChannelInterface $channel
     */
    public function __construct(
        Manga $manga,
        $parent,
        $channelName,
        $everyoneRole,
        Guild $guild,
        Client $client,
        TextChannelInterface $channel
    ) {
        $this->manga = $manga;
        $this->parent = $parent;
        $this->channelName = $channelName;
        $this->everyoneRole = $everyoneRole;
        $this->guild = $guild;
        $this->client = $client;
        $this->channel = $channel;
    }

    /**
     * @return Manga
     */
    public function getManga(): Manga
    {
        return $this->manga;
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
     * @return Guild
     */
    public function getGuild(): Guild
    {
        return $this->guild;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return TextChannelInterface
     */
    public function getChannel(): TextChannelInterface
    {
        return $this->channel;
    }
}
