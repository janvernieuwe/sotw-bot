<?php

namespace App;

use App\Message\EmojiNomination;
use RestCord\DiscordClient;
use RestCord\Model\Emoji\Emoji;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @return Emoji[]
     */
    public function getEmojis(): array
    {

        if ($this->emoji === null) {
            $this->emoji = $this->discord->emoji->listGuildEmojis(['guild.id' => $this->id]);
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

        return $this->discord->emoji->createGuildEmoji(
            [
                'guild.id' => $this->id,
                'name'     => $name,
                'image'    => $image,
            ]
        );
    }

    /**
     * @param string $name
     * @return Emoji
     */
    public function getEmojiByName(string $name): Emoji
    {
        foreach ($this->getEmojis() as $emoji) {
            if ($emoji->name === $name) {
                return new Emoji((array)$emoji);
            }
        }

        throw new NotFoundHttpException("No emoji with name $name found");
    }

    /**
     * @param int $id
     */
    public function removeEmoji(int $id): void
    {
        $this->discord->emoji->deleteGuildEmoji(
            [
                'guild.id' => $this->id,
                'emoji.id' => $id,
            ]
        );
        sleep(2);
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
        return $this->discord->emoji->getGuildEmoji(
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
        return $this->discord->emoji->modifyGuildEmoji(
            [
                'guild.id' => $this->id,
                'emoji.id' => $id,
                'name'     => $name,
                'roles'    => $roles,
            ]
        );
    }

    /**
     * @param EmojiNomination $nomination
     * @return Emoji
     */
    public function addEmojiFromNomination(EmojiNomination $nomination): Emoji
    {
        $encodedData = base64_encode(file_get_contents($nomination->getUrl()));
        $info = pathinfo($nomination->getUrl());
        $image = sprintf('data:image/%s;base64,%s', $info['extension'], $encodedData);

        sleep(1);
        return $this->addEmoji($nomination->getName(), $image);
    }
}
