<?php

namespace App\Message;

use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\MessageReaction;

/**
 * Class YasminEmojiNomination
 *
 * @package App\Message
 */
class YasminEmojiNomination
{
    /**
     * @var Message
     */
    private $message;

    /**
     * YasminEmojiNomination constructor.
     *
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->message->reactions->count() !== 1) {
            return false;
        }

        return preg_match('/^<:\w+:\d+>$/', $this->message->content);
    }

    /**
     * @return int
     */
    public function getVotes(): int
    {
        /** @var MessageReaction $reaction */
        $reaction = $this->message->reactions->first();
        if ($reaction === null) {
            return 0;
        }

        return (int)$reaction->count;
    }

    /**
     * @return bool
     */
    public function isOnServer(): bool
    {
        return $this->message->guild->emojis->keyBy('name')->get($this->getEmojiName()) !== null;
    }

    /**
     * @return string
     */
    public function getEmojiName(): string
    {
        preg_match('/<:([^:]+):\d+>/i', $this->getContent(), $matches);

        return $matches[1];
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->message->content;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        preg_match('/<:[^:]+:([^>]+)>/', $this->getContent(), $matches);

        return sprintf('https://cdn.discordapp.com/emojis/%s.png?v=1', $matches[1]);
    }
}
