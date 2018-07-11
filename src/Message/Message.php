<?php

namespace App\Message;

use CharlotteDunois\Yasmin\Models\Message as YasminMessage;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Message
 *
 * @package App\Message
 * @deprecated
 */
final class Message
{
    /**
     * @Assert\Type(type="array", message="Message is not an array")
     * @var array
     */
    protected $message;

    /**
     * Message constructor.
     *
     * @param array $message
     */
    public function __construct(array $message)
    {
        $this->message = $message;
    }

    /**
     * @param YasminMessage $message
     *
     * @return array
     */
    protected static function yasminToArray(YasminMessage $message): array
    {
        return [
            'id'      => $message->id,
            'content' => $message->content,
            'author'  => [
                'username' => $message->author->username,
                'id'       => $message->author->id,
            ],
        ];
    }

    /**
     * @return int
     * @Assert\Type(type="int", message="Invalid message id")
     */
    public function getMessageId(): int
    {
        return (int)$this->message['id'];
    }

    /**
     * @Assert\Type(type="string", message="Invalid author")
     * @Assert\NotBlank(message="Missing author")
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->message['author']['username'];
    }

    /**
     * @Assert\Type(type="int", message="Missing author id")
     * @return int
     */
    public function getAuthorId(): int
    {
        return (int)$this->message['author']['id'];
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->message['content'];
    }

    /**
     * @param string $emoji
     * @param bool   $onlyMe
     *
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
        if (!\is_array($this->message)) {
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

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return new \DateTime($this->message['timestamp']);
    }
}
