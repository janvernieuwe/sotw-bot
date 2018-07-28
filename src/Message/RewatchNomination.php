<?php

namespace App\Message;

use App\Entity\Reaction;
use App\Entity\RewatchWinner;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\MessageReaction;
use Jikan\Helper\Parser;
use Jikan\Model\Anime\Anime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SotwNomination
 *
 * @package App\Entity
 */
class RewatchNomination
{
    /**
     * @var Anime
     */
    private $anime;

    /**
     * @var RewatchWinner
     */
    private $previous;

    /**
     * @var bool
     */
    private $uniqueAnime = true;

    /**
     * @var bool
     */
    private $uniqueUser = true;

    /**
     * @var int
     */
    private $animeId;

    /**
     * @var int
     */
    private $votes = 0;

    /**
     * @var string
     */
    private $author;

    /**
     * @var int
     */
    private $authorId;

    /**
     * @var int
     */
    private $messageId;

    /**
     * @param string $content
     *
     * @return bool
     */
    public static function isContender(string $content): bool
    {
        return preg_match('/^https?:\/\/myanimelist\.net\/anime/', $content);
    }

    /**
     * @param $message
     *
     * @return RewatchNomination
     */
    public static function fromMessage(Message $message): RewatchNomination
    {
        $instance = new self();
        $instance->animeId = Parser::idFromUrl($message->content);
        $instance->author = $message->author->username;
        $instance->authorId = $message->author->id;
        $instance->messageId = $message->id;
        $instance->votes = $message->reactions->get(Reaction::VOTE);
        $instance->votes = $instance->votes instanceof MessageReaction ? $instance->votes->count - 1 : 0;

        return $instance;
    }

    /**
     * @return int|null
     */
    public function getAnimeId(): ?int
    {
        return $this->animeId;
    }

    /**
     * @Assert\GreaterThanOrEqual(value="10", message="Te weinig afleveringen (minstens 10)")
     * @Assert\LessThanOrEqual(value="26", message="Te veel afleveringen (maximaal 26)")
     *
     * @return int|null
     */
    public function getEpisodeCount(): ?int
    {
        if (!$this->anime instanceof Anime) {
            return null;
        }

        return $this->anime->getEpisodes();
    }

    /**
     * @return Anime
     */
    public function getAnime(): Anime
    {
        return $this->anime;
    }

    /**
     * @param Anime $anime
     */
    public function setAnime(Anime $anime): void
    {
        $this->anime = $anime;
    }

    /**
     * @Assert\IsFalse(message="Geen hentai!")
     * @return bool
     */
    public function isHentai(): bool
    {
        foreach ($this->anime->getGenres() as $genre) {
            if ($genre->getName() === 'Hentai') {
                return true;
            }
        }

        return false;
    }

    /**
     * @Assert\IsTrue(message="Anime is te nieuw")
     * @return bool
     */
    public function isValidDate(): bool
    {
        $max = new \DateTime('-2 years');

        return !($this->getEndDate() > $max);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEndDate(): \DateTimeImmutable
    {
        return $this->anime->getAired()->getUntil() ?? new \DateTimeImmutable();
    }

    /**
     * @return int
     * @Assert\Range(
     *     min="20",
     *     max="30",
     *     maxMessage="De lengte van een aflevering moet tussen de 20 en 30 minuten zijn",
     *     minMessage="De lengte van een aflevering moet tussen de 20 en 30 minuten zijn",
     * )
     */
    public function getEpisodeLength(): int
    {
        if (!preg_match('/^(\d+)/', $this->anime->getDuration(), $length)) {
            return 0;
        }

        return (int)$length[1];
    }

    /**
     * @return RewatchWinner
     */
    public function getPrevious(): RewatchWinner
    {
        return $this->previous;
    }

    /**
     * @param RewatchWinner $previous
     */
    public function setPrevious(RewatchWinner $previous = null): void
    {
        $this->previous = $previous;
    }

    /**
     * @return bool
     * @Assert\IsTrue(message="Je nominatie heeft al eens gewonnen")
     */
    public function getValidatePrevious(): bool
    {
        return $this->previous === null;
    }

    /**
     * @Assert\IsTrue(message="Deze anime is reeds genomineerd")
     * @return bool
     */
    public function isUniqueAnime(): bool
    {
        return $this->uniqueAnime;
    }

    /**
     * @param bool $uniqueAnime
     */
    public function setUniqueAnime(bool $uniqueAnime): void
    {
        $this->uniqueAnime = $uniqueAnime;
    }

    /**
     * @Assert\IsTrue(message="Je hebt al een nominatie gemaakt")
     * @return bool
     */
    public function isUniqueUser(): bool
    {
        return $this->uniqueUser;
    }

    /**
     * @param bool $uniqueUser
     */
    public function setUniqueUser(bool $uniqueUser): void
    {
        $this->uniqueUser = $uniqueUser;
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
    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {
        return $this->messageId;
    }
}
