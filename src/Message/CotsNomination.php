<?php

namespace App\Message;

use App\Entity\Reaction;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\MessageReaction;
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
     * @var string
     */
    private $author;

    /**
     * @var int
     */
    private $authorid;

    /**
     * @var int
     */
    private $votes;

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
     * @param Message   $message
     * @param Anime     $anime
     * @param Character $character
     *
     * @return CotsNomination
     */
    public static function fromMessage(Message $message, Character $character, Anime $anime): CotsNomination
    {
        $instance = new self();
        $instance->anime = $anime;
        $instance->character = $character;
        $instance->votes = $message->reactions->get(Reaction::VOTE);
        $instance->votes = $instance->votes instanceof MessageReaction ? $instance->votes->count - 1 : 0;
        $instance->author = $message->author->username;
        $instance->authorid = $message->author->id;

        return $instance;
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
        foreach ($this->character->getAnimeography() as $anime) {
            if ($anime->getMalId() === $this->anime->getMalId()) {
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

    /**
     * @return int
     */
    public function getVotes(): int
    {
        return $this->votes;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @return int
     */
    public function getAuthorid(): int
    {
        return $this->authorid;
    }
}
