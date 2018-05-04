<?php

namespace App\Message;

use App\Channel\Channel;
use App\Exception\InvalidChannelException;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\TextChannel;

/**
 * Class JoinableChannelMessage
 * @package App\Message
 */
class SimpleJoinableChannelMessage
{
    public const CHANNEL_REGXP = '/(c=)(\d+)/';
    public const JOIN_REACTION = '▶';
    public const LEAVE_REACTION = '⏹';
    public const DELETE_REACTION = '🚮';
    public const RELOAD_REACTION = '🔁';
    public const TEXT_MESSAGE = '';

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
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     * @return bool
     */
    public static function isJoinableChannel(\CharlotteDunois\Yasmin\Models\Message $message): bool
    {
        return preg_match(self::CHANNEL_REGXP, $message->content);
    }

    /**
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     * @return bool
     */
    public static function isJoinChannelMessage(\CharlotteDunois\Yasmin\Models\Message $message): bool
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $message = new self($message);

        return $message->getFieldValue('Channel') !== null;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getFieldValue(string $key)
    {
        if (!count($this->message->embeds)) {
            return null;
        }
        $data = array_filter(
            $this->message->embeds[0]->fields,
            function (array $data) use ($key) {
                return $data['name'] === $key;
            }
        );
        if (!count($data)) {
            return null;
        }
        $data = array_values($data);

        return $data[0]['value'];
    }

    /**
     * @param GuildMember $member
     */
    public function addUser(GuildMember $member): void
    {
        // No double joins
        if ($this->hasAccess($member->id)) {
            return;
        }
        // Join channel
        $channel = $this->getChannelFromMessage();
        $channel->overwritePermissions(
            $member->id,
            Channel::ROLE_VIEW_MESSAGES,
            0,
            'User joined the channel'
        );
        // Update the member counf
        $count = $this->getSubsciberCount() + 1;
        $this->updateWatchers($count);
        // Announce join
        $joinMessage = sprintf(
            ':inbox_tray:  %s joined %s',
            Util::mention((int)$member->id),
            Util::channelLink((int)$channel->id)
        );
        $channel->send($joinMessage);
    }

    /**
     * @param int $memberid
     * @return bool
     */
    public function hasAccess(int $memberid): bool
    {
        $permissions = $this->getChannelFromMessage()->permissionOverwrites->all();
        $view = array_filter(
            $permissions,
            function (PermissionOverwrite $o) use ($memberid) {
                return $o->allow->bitfield === Channel::ROLE_VIEW_MESSAGES
                    && $memberid === (int)$o->id
                    && $o->type === 'member';
            }
        );

        return count($view) > 0;
    }

    /**
     * @return TextChannel
     * @throws InvalidChannelException
     */
    public function getChannelFromMessage(): TextChannel
    {
        $channel = $this->message->guild->channels->get($this->getChannelId());
        if ($channel === null) {
            throw new InvalidChannelException('Channel not found');
        }

        return $channel;
    }

    /**
     * @return int|null
     */
    public function getChannelId(): ?int
    {
        return (int)preg_replace('/\D*/', '', $this->getFieldValue('Channel'));
    }

    /**
     * @return int
     */
    public function getSubsciberCount(): int
    {
        return count($this->getSubscribers()) - 1;
    }

    /**
     * @return array
     */
    public function getSubscribers(): array
    {
        return array_filter(
            $this->getChannel()->permissionOverwrites->all(),
            function (PermissionOverwrite $o) {
                return $o->allow->bitfield === Channel::ROLE_VIEW_MESSAGES;
            }
        );
    }

    /**
     * @return TextChannel
     */
    public function getChannel(): TextChannel
    {
        return $this->message->guild->channels->get($this->getChannelId());
    }

    /**
     * @return string
     */
    public function getChannelTopic(): string
    {
        return $this->getChannel()->topic;
    }

    /**
     * Update the the channel message
     * @param int $count
     */
    public function updateWatchers(int $count): void
    {
        $embed = static::generateRichChannelMessage($this->getChannelId(), $count, $this->getChannelTopic());
        $this->message->edit(self::TEXT_MESSAGE, $embed);
    }

    /**
     * @param int $channelId
     * @param int $subsciberCount
     * @param string $message
     * @return array
     */
    public static function generateRichChannelMessage(int $channelId, int $subsciberCount, string $message): array
    {
        return [
            'embed' => [
                'footer' => [
                    'text' => 'Druk op de reactions om te joinen / leaven',
                ],
                'fields' => [
                    [
                        'name'   => 'Description',
                        'value'  => $message,
                        'inline' => false,
                    ],
                    [
                        'name'   => 'Channel',
                        'value'  => Util::channelLink($channelId),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Members',
                        'value'  => $subsciberCount,
                        'inline' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param GuildMember $member
     */
    public function removeUser(GuildMember $member): void
    {
        // No double joins
        if (!$this->hasAccess($member->id)) {
            return;
        }
        // Remove member
        $channel = $this->getChannelFromMessage();
        $channel->overwritePermissions(
            $member->id,
            0,
            Channel::ROLE_VIEW_MESSAGES,
            'User left the channel'
        );
        // Update member count
        $count = $this->getSubsciberCount() - 1;
        $this->updateWatchers($count);
        // Announce leave
        $channel->send(
            sprintf(
                ':outbox_tray: %s left %s',
                Util::mention((int)$member->id),
                Util::channelLink($this->getChannelId())
            )
        );
    }
}
