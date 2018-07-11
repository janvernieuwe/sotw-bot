<?php

namespace App\Context;

use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Guild;

/**
 * Class CreateSimpleChannelContext
 *
 * @package App\Context
 */
class CreateSimpleChannelContext
{
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
     * @var string
     */
    private $description;

    /**
     * CreateSimpleChannelContext constructor.
     *
     * @param int                  $parent
     * @param string               $channelName
     * @param string               $description
     * @param int                  $everyoneRole
     * @param Guild                $guild
     * @param Client               $client
     * @param TextChannelInterface $channel
     */
    public function __construct(
        int $parent,
        string $channelName,
        string $description,
        int $everyoneRole,
        Guild $guild,
        Client $client,
        TextChannelInterface $channel
    ) {
        $this->parent = $parent;
        $this->channelName = $channelName;
        $this->everyoneRole = $everyoneRole;
        $this->guild = $guild;
        $this->client = $client;
        $this->channel = $channel;
        $this->description = $description;
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

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
