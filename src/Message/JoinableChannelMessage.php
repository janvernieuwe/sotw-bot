<?php

namespace App\Message;

use App\Channel\Channel;
use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\TextChannel;

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
    public const RELOAD_REACTION = '🔁';

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

    /**
     * @return int|null
     */
    public function getAnimeId(): ?int
    {
        if (preg_match('#https?://myanimelist.net/anime/(\d+)#', $this->message->content, $channel)) {
            return (int)$channel[1];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getAnimeLink(): ?string
    {
        if (preg_match('#https?://myanimelist.net/anime/\S+#', $this->message->content, $channel)) {
            return $channel[0];
        }

        return '';
    }

    /**
     * @param TextChannel $channel
     * @return int
     */
    public function getSubsciberCount(TextChannel $channel): int
    {
        return count($this->getSubscribers($channel)) -1;
    }

    /**
     * @param TextChannel $channel
     * @return array
     */
    public function getSubscribers(TextChannel $channel): array
    {
        return array_filter(
            $channel->permissionOverwrites->all(),
            function (PermissionOverwrite $o) {
                return $o->allow->bitfield === Channel::ROLE_VIEW_MESSAGES;
            }
        );
    }
}
