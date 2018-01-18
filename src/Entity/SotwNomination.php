<?php

namespace App\Entity;

use Psr\Log\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SotwNomination
 * @package App\Entity
 */
class SotwNomination
{
    /**
     * @var string
     * @Assert\Type(type="string", message="Invalid message")
     * @Assert\NotBlank(message="Missing message")
     */
    private $lines;

    /**
     * @Assert\Type(type="array", message="Message is not an array")
     * @var array
     */
    private $message;

    /**
     * @var string
     * @Assert\Type(type="string", message="Invalid author")
     * @Assert\NotBlank(message="Missing author")
     */
    private $author;

    /**
     * @var int
     * @Assert\Type(type="int", message="Missing author id")
     */
    private $authorId;


    /**
     * @var string
     * @Assert\Type(type="string", message="Invalid artist")
     * @Assert\NotBlank(message="Missing artist")
     */
    private $artist;

    /**
     * @var string
     * @Assert\Type(type="string", message="Invalid title")
     * @Assert\NotBlank(message="Missing title")
     */
    private $title;

    /**
     * @var string
     * @Assert\Type(type="string", message="Invalid anime")
     * @Assert\NotBlank(message="Missing anime")
     */
    private $anime;

    /**
     * @var int
     * @Assert\Type(type="string", message="Invalid message id")
     * @Assert\NotBlank(message="Missing message id")
     */
    private $messageId;

    /**
     * @Assert\Type(type="string", message="Invalid youtube line")
     * @Assert\NotBlank(message="Missing youtube link")
     * @var string
     */
    private $youtube;

    /**
     * @param string $data
     * @return bool
     */
    public static function isContenter(string $data): bool
    {
        return preg_match('#https?://(www\.)?youtube\.com#im', $data);
    }

    /**
     * @param array $message
     * @return SotwNomination
     */
    public static function fromMessage(array $message): SotwNomination
    {
        $nominee = new self();
        $nominee->message = $message;
        $nominee->lines = $content = str_replace('(edited)', '', $message['content']);
        $nominee->artist = self::matchPattern('artist', $content);
        $nominee->title = self::matchPattern('title', $content);
        $nominee->anime = self::matchPattern('anime', $content);
        $nominee->youtube = self::matchPattern('url', $content);
        $nominee->author = $message['author']['username'];
        $nominee->authorId = (int)$message['author']['id'];
        $nominee->messageId = $message['id'];

        return $nominee;
    }

    /**
     * @param string $pattern
     * @param string $content
     * @return string
     */
    protected static function matchPattern(string $pattern, string $content): string
    {
        $pattern = sprintf('/%s\:\s?(.*)/im', $pattern);
        preg_match_all($pattern, $content, $matches);
        if (!isset($matches[1][0])) {
            return '';
        }

        return str_replace(['[', ']'], ' ', $matches[1][0]);
    }

    /**
     * @return mixed
     */
    public function getTrackInfo()
    {
        $lines = preg_split('/^YouTube$/m', $this->lines);
        /** @noinspection SuspiciousAssignmentsInspection */
        $lines = array_values(array_filter(explode(PHP_EOL, $lines[1])));

        return $lines[1];
    }

    /**
     * @return int
     */
    public function getVotes(): int
    {
        if (!array_key_exists('reactions', $this->message)) {
            return 0;
        }
        $reactions = array_filter(
            $this->message['reactions'],
            function (array $reaction) {
                return $reaction['emoji']['name'] === '🔼';
            }
        );
        $reactions = array_values($reactions);
        if (!\count($reactions)) {
            return 0;
        }

        return $reactions[0]['count'];
    }

    /**
     * @return string
     */
    public function getYoutubeCode(): string
    {
        preg_match_all('/([\w]*)$/', $this->getYoutube(), $matches);
        if (!isset($matches[1][0])) {
            throw new InvalidArgumentException('No yt code '.$this->getYoutube());
        }

        return $matches[1][0];
    }

    /**
     * @return string
     */
    public function getYoutube(): string
    {
        return $this->youtube;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s - %s (%s) door %s',
            $this->getArtist(),
            $this->getTitle(),
            $this->getAnime(),
            $this->getAuthor()
        );
    }

    /**
     * @return string
     */
    public function getArtist(): string
    {
        return $this->artist;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getAnime(): string
    {
        return $this->anime;
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

    /**
     * @param string $emoji
     * @param bool $onlyMe
     * @return bool
     */
    public function hasReaction(string $emoji, bool $onlyMe = true): bool
    {
        if (!isset($this->message['reactions'])) {
            return false;
        }
        /** @noinspection ForeachSourceInspection */
        foreach ($this->message['reactions'] as $reaction) {
            if ($reaction['emoji']['name'] !== $emoji) {
                continue;
            }
            if ($onlyMe && !$reaction['me']) {
                continue;
            }

            return true;
        }

        return false;
    }
}
