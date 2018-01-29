<?php

namespace App\Message;

/**
 * Class Message
 * @package App\Message
 */
class Message
{
    /**
     * @var string
     * @Assert\Type(type="string", message="Invalid author")
     * @Assert\NotBlank(message="Missing author")
     */
    protected $author;

    /**
     * @var int
     * @Assert\Type(type="int", message="Missing author id")
     */
    protected $authorId;

    /**
     * @Assert\Type(type="array", message="Message is not an array")
     * @var array
     */
    protected $message;

    /**
     * @var int
     * @Assert\Type(type="string", message="Invalid message id")
     * @Assert\NotBlank(message="Missing message id")
     */
    protected $messageId;

    /**
     * @return int
     */
    public function getMessageId(): int
    {
        return $this->messageId;
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

    /**
     * @return int
     */
    public function getVotes(): int
    {
        if (!is_array($this->message)) {
            return 0;
        }
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
}
