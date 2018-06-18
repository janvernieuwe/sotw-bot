<?php

namespace App\Message;

use CharlotteDunois\Yasmin\Models\Message;

/**
 * Class YasminEmojiNomination
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
}
