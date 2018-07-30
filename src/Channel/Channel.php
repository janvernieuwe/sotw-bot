<?php

namespace App\Channel;

use App\Exception\InvalidChannelException;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\Permissions;
use CharlotteDunois\Yasmin\Models\Role;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Models\User;
use React\Promise\PromiseInterface;

/**
 * Class Channel
 *
 * @package App\Channel
 */
class Channel
{
    public const ROLE_SEND_MESSAGES = 0x00000800;
    public const ROLE_VIEW_MESSAGES = 0x00000400;
    public const CHANNEL_KEY = 'channel';

    public static function getUserCount(TextChannel $channel): int
    {
        $members = array_filter(
            $channel->permissionOverwrites->all(),
            function (PermissionOverwrite $o) use ($channel) {
                if ($o->type !== 'member') {
                    return false;
                }
                /** @var User $user */
                $user = $channel->client->users->get($o->id);
                if ($user->bot) {
                    return false;
                }

                return $o->allow->has(Channel::ROLE_VIEW_MESSAGES);
            }
        );

        return \count($members);
    }

    /**
     * Get the channel id from a joinable channel message
     *
     * @param Message $message
     *
     * @return int|null
     * @throws InvalidChannelException
     */
    public static function getChannelId(Message $message): ?int
    {
        $channel = self::getFieldValue($message, self::CHANNEL_KEY);
        if ($channel === null) {
            return null;
        }

        return (int)preg_replace('/\D/', '', $channel);
    }

    /**
     * @param Message $message
     *
     * @return GuildChannelInterface
     * @throws InvalidChannelException
     */
    public static function getGuildChannel(Message $message): GuildChannelInterface
    {
        return $message->guild->channels->get(self::getChannelId($message));
    }

    /**
     * @param Message $message
     *
     * @return TextChannelInterface|TextChannel
     * @throws InvalidChannelException
     */
    public static function getTextChannel(Message $message): TextChannelInterface
    {
        return $message->client->channels->get(self::getChannelId($message));
    }


    /**
     * @param Message $message
     * @param string  $key
     *
     * @return null|string
     * @throws InvalidChannelException
     */
    public static function getFieldValue(Message $message, string $key): ?string
    {
        if (!count($message->embeds)) {
            return null;
        }
        $data = array_filter(
            $message->embeds[0]->fields,
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
     * @param GuildChannelInterface   $channel
     * @param string|GuildMember|Role $role
     *
     * @return PromiseInterface
     */
    public static function open(GuildChannelInterface $channel, $role): PromiseInterface
    {
        $permissions = new Permissions();
        $permissions->add(self::ROLE_SEND_MESSAGES);
        $permissions->add(self::ROLE_VIEW_MESSAGES);

        return $channel->overwritePermissions(
            $role,
            $permissions,
            0,
            'Opened channel'
        );
    }

    /**
     * @param GuildChannelInterface   $channel
     * @param string|GuildMember|Role $role
     *
     * @return PromiseInterface
     */
    public static function close(GuildChannelInterface $channel, $role): PromiseInterface
    {
        return $channel->overwritePermissions(
            $role,
            self::ROLE_VIEW_MESSAGES,
            self::ROLE_SEND_MESSAGES,
            'Closed channel'
        );
    }

    /**
     * @param Message $message
     * @param int     $memberid
     *
     * @return bool
     * @throws InvalidChannelException
     */
    public static function hasAccess(Message $message, int $memberid): bool
    {
        $permissions = self::getTextChannel($message)->permissionOverwrites->all();
        $view = array_filter(
            $permissions,
            function (PermissionOverwrite $o) use ($memberid) {
                return $o->allow->has(Channel::ROLE_VIEW_MESSAGES)
                    && $memberid === (int)$o->id
                    && $o->type === 'member';
            }
        );

        return count($view) > 0;
    }
}
