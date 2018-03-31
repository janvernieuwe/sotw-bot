<?php

namespace App\Message;

use Jikan\Jikan;
use Jikan\Model\Anime;
use Jikan\Model\Character;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CotsNomination
 * @package App\Message
 */
class CotsNomination extends Message
{
    const CHARACTER_REGXP = '#https?://myanimelist.net/character/(\d+)#';
    const ANIME_REGXP = '#https?://myanimelist.net/anime/(\d+)#';

    /**
     * @var Character
     */
    private $character;

    /**
     * @var Anime
     */
    private $anime;

    /**
     * @var string
     */
    private $season = '';

    /**
     * CotsNomination constructor.
     * @param array $message
     * @param Character $character
     * @param Anime $anime
     */
    public function __construct(array $message, Character $character, Anime $anime)
    {
        parent::__construct($message);
        $this->character = $character;
        $this->anime = $anime;
    }

    /**
     * @param string $content
     * @return bool
     */
    public static function isNomination(string $content): bool
    {
        return preg_match(self::CHARACTER_REGXP, $content) && preg_match(self::ANIME_REGXP, $content);
    }

    /**
     * @param string $content
     * @return int
     */
    public static function getCharacterId(string $content): int
    {
        if (!preg_match(self::CHARACTER_REGXP, $content, $matches)) {
            return 0;
        }

        return (int)$matches[1];
    }

    /**
     * @param string $content
     * @return int
     */
    public static function getAnimeId(string $content): int
    {
        if (!preg_match(self::ANIME_REGXP, $content, $matches)) {
            return 0;
        }

        return (int)$matches[1];
    }

    /**
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     * @param Character $character
     * @param Anime $anime
     * @return CotsNomination
     */
    public static function fromYasmin(
        \CharlotteDunois\Yasmin\Models\Message $message,
        Character $character,
        Anime $anime
    ): CotsNomination {
        return new self(parent::yasminToArray($message), $character, $anime);
    }

    /**
     * @param string $season
     */
    public function setSeason(string $season)
    {
        $this->season = $season;
    }

    /**
     * @return Character
     */
    public function getCharacter(): Character
    {
        return $this->character;
    }

    /**
     * @return Anime
     */
    public function getAnime(): Anime
    {
        return $this->anime;
    }

    /**
     * @Assert\IsTrue(message="Je character komt niet voor in je anime")
     * @return bool
     */
    public function getIsCharacterInAnime(): bool
    {
        foreach ($this->character->animeography as $anime) {
            if ($anime['mal_id'] === $this->anime->mal_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @Assert\IsTrue(message="Je serie is niet van het juiste seizoen!")
     * @return bool
     */
    public function getIsValidSeason(): bool
    {
        return $this->season === $this->anime->premiered;
    }
}
