<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Channel\Channel;
use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\TextChannel;

/**
 * Trait AccessCheckingTrait
 * @package App\Yasmin\Subscriber\AnimeChannel
 */
trait AccessCheckingTrait
{
    /**
     * @param TextChannel $channel
     * @param int $id
     * @return bool
     */
    public function hasAccess(TextChannel $channel, int $id): bool
    {
        $view = array_filter(
            $channel->permissionOverwrites->all(),
            function (PermissionOverwrite $o) use ($id) {
                return $o->allow->bitfield === Channel::ROLE_VIEW_MESSAGES
                    && $id === (int)$o->id
                    && $o->type === 'member';
            }
        );

        return count($view) > 0;
    }
}
