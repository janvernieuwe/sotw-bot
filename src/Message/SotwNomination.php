<?php

namespace App\Message;

use Psr\Log\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SotwNomination
 * @package App\Entity
 */
class SotwNomination extends Message
{
    /**
     * @var string
     * @Assert\Type(type="string", message="Artiest is ongeldig")
     * @Assert\NotBlank(message="Artiest ontbreekt")
     */
    private $artist;

    /**
     * @var string
     * @Assert\Type(type="string", message="Titel is ongeldig")
     * @Assert\NotBlank(message="Titel ontbreekt")
     */
    private $title;

    /**
     * @var string
     * @Assert\Type(type="string", message="Anime is ongeldig")
     * @Assert\NotBlank(message="Anime ontbreekt")
     */
    private $anime;

    /**
     * @Assert\Type(type="string", message="Youtube link is ongeldig")
     * @Assert\NotBlank(message="Youtube link ontbreekt")
     * @var string
     */
    private $youtube;

    /**
     * @param string $data
     * @return bool
     */
    public static function isContenter(string $data): bool
    {
        return preg_match('#https?://(www\.|m.)?(youtube\.com|youtu\.be)#im', $data);
    }

    /**
     * @param array $message
     * @return SotwNomination
     */
    public static function fromMessage(array $message): SotwNomination
    {
        $nominee = new self($message);
        $nominee->artist = self::matchPattern('artist', $message['content']);
        $nominee->title = self::matchPattern('title', $message['content']);
        $nominee->anime = self::matchPattern('anime', $message['content']);
        $nominee->youtube = self::matchPattern('url', $message['content']);

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

        $match = str_replace(['[', ']'], ' ', $matches[1][0]);

        return trim($match);
    }

    /**
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     * @return SotwNomination
     */
    public static function fromYasmin(\CharlotteDunois\Yasmin\Models\Message $message): SotwNomination
    {
        return self::fromMessage(parent::yasminToArray($message));
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
}
