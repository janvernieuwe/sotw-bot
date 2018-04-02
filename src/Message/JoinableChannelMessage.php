<?php

namespace App\Message;

/**
 * Class JoinableChannelMessage
 * @package App\Message
 */
class JoinableChannelMessage
{
    public const CHANNEL_REGXP = '/(c=)(\d+)/';
    public const JOIN_REACTION = '▶';
    public const LEAVE_REACTION = '⏹';
    public const DELETE_REACTION = '🚮';

    /**
     * @var \CharlotteDunois\Yasmin\Models\Message
     */
    private $message;

    /**
     * JoinableChannelMessage constructor.
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     */
    public function __construct(\CharlotteDunois\Yasmin\Models\Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $content
     * @return bool
     */
    public static function isJoinableChannel(string $content): bool
    {
        return preg_match(self::CHANNEL_REGXP, $content);
    }

    /**
     * @return int|null
     */
    public function getChannelId(): ?int
    {
        if (preg_match(self::CHANNEL_REGXP, $this->message->content, $channel)) {
            return (int)$channel[2];
        }

        return null;
    }
}
