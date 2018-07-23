<?php

namespace App\Message;

use CharlotteDunois\Yasmin\Models\Message;
use Jikan\Model\Anime\Anime;
use Jikan\Model\Character\Character;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CotsNomination
 *
 * @package App\Message
 */
class CotsNomination
{
    private const CHARACTER_REGXP = '#https?://myanimelist.net/character/(\d+)#';
    private const ANIME_REGXP = '#https?://myanimelist.net/anime/(\d+)#';

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
     * @var array|Message
     */
    private $message;

    /**
     * CotsNomination constructor.
     *
     * @param Character $character
     * @param Anime     $anime
     */
    public function __construct(Character $character, Anime $anime)
    {
        $this->character = $character;
        $this->anime = $anime;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public static function isNomination(string $content): bool
    {
        return preg_match(self::CHARACTER_REGXP, $content) && preg_match(self::ANIME_REGXP, $content);
    }

    /**
     * @param string $content
     *
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
     *
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
     * @param string $season
     */
    public function setSeason(string $season): void
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
            if ($anime['mal_id'] === $this->anime->getMalId()) {
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
        return $this->season === $this->anime->getPremiered();
    }
}
