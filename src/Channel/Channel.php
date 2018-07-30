<?php

namespace App\Channel;

use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Models\User;

/**
 * Class Channel
 *
 * @package App\Channel
 */
class Channel
{
    public const ROLE_SEND_MESSAGES = 0x00000800;
    public const ROLE_VIEW_MESSAGES = 0x00000400;

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
}
