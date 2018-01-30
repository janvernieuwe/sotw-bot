<?php

namespace App;

use GuzzleHttp\Command\Result;
use RestCord\DiscordClient;
use RestCord\Model\Guild\Emoji;

class Guild
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var DiscordClient
     */
    private $discord;

    /**
     * @var array
     */
    private $emoji;

    /**
     * Guild constructor.
     * @param int $id
     * @param DiscordClient $discord
     */
    public function __construct(DiscordClient $discord, int $id)
    {
        $this->id = $id;
        $this->discord = $discord;
    }

    /**
     * @return array
     */
    public function getEmojis(): array
    {

        if ($this->emoji === null) {
            /** @var Result $emoji */
            $emoji = $this->discord->guild->listGuildEmoji(['guild.id' => $this->id]);
            $this->emoji = $emoji->toArray();
        }

        return $this->emoji;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasEmoji(string $name): bool
    {
        $name = $this->escapeEmoji($name);
        foreach ($this->getEmojis() as $emoji) {
            if ($emoji->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     * @param string $image
     * @return Emoji
     */
    public function addEmoji(string $name, string $image): Emoji
    {
        $name = $this->escapeEmoji($name);

        return $this->discord->guild->createGuildEmoji(
            [
                'guild.id' => $this->id,
                'name'     => $name,
                'image'    => $image,
            ]
        );
    }

    public function escapeEmoji(string $name): string
    {
        return (string)preg_replace('/\W/', '', $name);
    }

    /**
     * @param int $id
     * @return Emoji
     */
    public function getEmoji(int $id): Emoji
    {
        return $this->discord->guild->getGuildEmoji(
            [
                'guild.id' => $this->id,
                'emoji.id' => $id,
            ]
        );
    }

    /**
     * @param int $id
     * @param string $name
     * @param array $roles
     * @return Emoji
     */
    public function modifyEmoji(int $id, string $name, array $roles = []): Emoji
    {
        return $this->discord->guild->modifyGuildEmoji(
            [
                'guild.id' => $this->id,
                'emoji.id' => $id,
                'name'     => $name,
                'roles'    => $roles,
            ]
        );
    }
}
