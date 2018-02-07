<?php

namespace App\Message;

use Jikan\Model\Anime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SotwNomination
 * @package App\Entity
 */
class RewatchNomination extends Message
{
    /**
     * @var Anime
     */
    private $anime;

    /**
     * @param array $message
     * @return RewatchNomination
     */
    public static function fromMessage(array $message): RewatchNomination
    {
        $nominee = new self($message);

        return $nominee;
    }

    /**
     * @return int|null
     */
    public function getAnimeId(): ?int
    {
        preg_match_all('/\/(\d+)\//', $this->message['content'], $matches);
        if (!isset($matches[1][0])) {
            return null;
        }
        return (int)$matches[1][0];
    }

    /**
     * @param string $content
     * @return bool
     */
    public static function isContender(string $content): bool
    {
        return preg_match('/https?:\/\/myanimelist\.net\/anime\/\d+\//', $content);
    }

    /**
     * @return int|null
     */
    public function getEpisodeCount(): ?int
    {
        if (!$this->anime instanceof Anime) {
            return null;
        }
        return $this->anime->episodes;
    }

    /**
     * @param Anime $anime
     */
    public function setAnime(Anime $anime): void
    {
        $this->anime = $anime;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate():\DateTime
    {
        return new \DateTime($this->anime->aired['to']);
    }

    /**
     * @return Anime
     */
    public function getAnime(): Anime
    {
        return $this->anime;
    }
}
