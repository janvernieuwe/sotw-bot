<?php

namespace App\Channel;

use CharlotteDunois\Yasmin\Models\Guild;
use CharlotteDunois\Yasmin\Models\Role;
use CharlotteDunois\Yasmin\Models\TextChannel;

/**
 * Class SeasonalAnimeChannel
 * @package App\Channel
 */
class SeasonalAnimeChannel
{
    /**
     * @var int
     */
    private $everyoneRole;

    /**
     * SeasonalAnimeChannel constructor.
     * @param int $everyoneRole
     */
    public function __construct(int $everyoneRole)
    {
        $this->everyoneRole = $everyoneRole;
    }

    /**
     * @param Guild $guild
     * @param $name
     */
    public function create(Guild $guild, $name): void
    {
        $guild
            ->createRole(
                [
                    'name'        => $name,
                    'permissions' => 0,
                    'mentionable' => false,
                ]
            )
            ->done(
                function (Role $role) use ($guild, $name) {
                    $guild->createChannel(
                        [
                            'name'                 => $name,
                            'topic'                => '',
                            'permissionOverwrites' => [
                                [
                                    'id'   => $this->everyoneRole,
                                    'deny' => Channel::ROLE_VIEW_MESSAGES,
                                    'type' => 'role',
                                ],
                                [
                                    'id'   => $role->id,
                                    'deny' => Channel::ROLE_VIEW_MESSAGES,
                                    'type' => 'role',
                                ],
                            ],
                            'parent'               => 430306918561611788,
                            'nsfw'                 => false,
                        ]
                    )->done(
                        function (TextChannel $channel) use ($guild, $role) {
                            $guild->channels->get(430305539948544010)->send(
                                sprintf("Test channel\nchannel id: %s\nrole id: %s", $channel->id, $role->id)
                            );
                        }
                    );
                }
            );
    }
}
