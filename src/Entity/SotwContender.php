<?php

namespace App\Entity;

use Psr\Log\InvalidArgumentException;

class SotwContender
{
    /**
     * @var mixed
     */
    private $lines;

    /**
     * @var array
     */
    private $message;

    /**
     * Contender constructor.
     * @param array $message
     */
    public function __construct(array $message)
    {
        $this->message = $message;
        $this->lines = str_replace('(edited)', '', $message['content']);
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->message['author']['username'];
    }

    /**
     * @return int
     */
    public function getAuthorId(): int
    {
        return (int)$this->message['author']['id'];
    }

    public function getMessageId(): int
    {
        return (int)$this->message['id'];
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
     * @return mixed
     */
    public function getYoutube()
    {
        return $this->matchPattern('url');
    }

    /**
     * @param $pattern
     * @return mixed
     */
    protected function matchPattern($pattern)
    {
        $pattern = sprintf('/%s\:\s?(.*)/im', $pattern);
        preg_match_all($pattern, $this->lines, $matches);

        return str_replace(['[', ']'], ' ', $matches[1][0]);
    }

    /**
     * @return mixed
     */
    public function getArtist()
    {
        return $this->matchPattern('artist');
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->matchPattern('title');
    }

    /**
     * @return mixed
     */
    public function getAnime()
    {
        return $this->matchPattern('anime');
    }

    /**
     * @param string $data
     * @return bool
     */
    public static function isContenter(string $data): bool
    {
        return preg_match('/^artist:/i', $data);
    }

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
}
