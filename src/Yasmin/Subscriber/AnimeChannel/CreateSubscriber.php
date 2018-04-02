<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Channel\Channel;
use App\Channel\SeasonalAnimeChannel;
use App\Message\JoinableChannelMessage;
use App\MyAnimeList\MyAnimeListClient;
use App\Util\Util;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Guild;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\Role;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Jikan\Model\Anime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function GuzzleHttp\Psr7\parse_query;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class CreateSubscriber implements EventSubscriberInterface
{
    /**
     * @var Anime
     */
    protected $anime;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $link;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var SeasonalAnimeChannel
     */
    protected $channel;

    /**
     * @var int
     */
    protected $everyoneRole;

    /**
     * @var MyAnimeListClient
     */
    private $mal;

    /**
     * @var int
     */
    private $seasonalCategoryId;

    /**
     * CreateSubscriber constructor.
     * @param SeasonalAnimeChannel $channel
     * @param MyAnimeListClient $mal
     * @param int $everyoneRole
     * @param int $seasonalCategoryId
     */
    public function __construct(
        SeasonalAnimeChannel $channel,
        MyAnimeListClient $mal,
        int $everyoneRole,
        int $seasonalCategoryId
    ) {
        $this->channel = $channel;
        $this->everyoneRole = $everyoneRole;
        $this->mal = $mal;
        $this->seasonalCategoryId = $seasonalCategoryId;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $io = $event->getIo();
        $this->message = $message = $event->getMessage();
        /** @var Client client */
        $this->client = $event->getMessage()->client;
        $matchCommand = preg_match('/^(\!haamc create\-channel )([\S]*)\s?(.*)$/', $message->content, $name);
        if (!$matchCommand || !$event->isAdmin()) {
            return;
        }
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $this->link = $name[3];
        $this->anime = $this->mal->loadAnime(MyAnimeListClient::getAnimeId($this->link));
        $name = $name[2];
        $guild = $message->guild;
        $this->createRole($guild, $name);
    }

    /**
     * @param Guild $guild
     * @param string $name
     */
    protected function createRole(Guild $guild, string $name)
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
                    $this->createChannel($role, $guild, $name);
                }
            );
    }

    /**
     * @param Role $role
     * @param Guild $guild
     * @param string $name
     */
    protected function createChannel(Role $role, Guild $guild, string $name)
    {
        $guild->createChannel(
            [
                'name'                 => $name,
                'topic'                => $name,
                'permissionOverwrites' => [
                    [
                        'id'   => $this->everyoneRole,
                        'deny' => Channel::ROLE_VIEW_MESSAGES,
                        'type' => 'role',
                    ],
                    [
                        'id'    => $role->id,
                        'allow' => Channel::ROLE_VIEW_MESSAGES,
                        'type'  => 'role',
                    ],
                    [
                        'id'    => $this->client->user->id,
                        'allow' => Channel::ROLE_VIEW_MESSAGES,
                        'type'  => 'member',
                    ],
                ],
                'parent'               => $this->seasonalCategoryId,
                'nsfw'                 => false,
            ]
        )->done(
            function (TextChannel $channel) use ($role) {
                $channel->setTopic(sprintf('%s || %s', $this->anime->title, $this->link));
                $channel->send(
                    sprintf(
                        ":tv: Hoi iedeen! in dit channel kijken we naar **%s**.\n%s",
                        $this->anime->title,
                        $this->link
                    )
                );
                $this->createJoinMessage($channel, $role);
            }
        );
    }

    /**
     * @param TextChannel $channel
     * @param Role $role
     */
    public function createJoinMessage(TextChannel $channel, Role $role)
    {
        $parts = parse_url($this->link);
        $query = parse_query($parts['query'] ?? '');
        $query['r'] = $role->id;
        $query['c'] = $channel->id;
        if (!preg_match('#https?://myanimelist.net/anime/\d+#', $this->link, $link)) {
            return;
        }
        $link = $link[0];
        $link .= '?'.http_build_query($query);
        $createChannelMessage = sprintf(
            ":tv: Kijk nu mee naar **%s**\nChannel: %s\nAnime: %s",
            $this->anime->title,
            Util::channelLink((int)$channel->id),
            $link
        );

        $this->message->channel
            ->send($createChannelMessage)
            ->done(
                function (Message $message) {
                    $this->addReactions($message);
                }
            );
    }

    /**
     * @param Message $message
     */
    public function addReactions(Message $message)
    {
        $message->react(JoinableChannelMessage::JOIN_REACTION);
        $message->react(JoinableChannelMessage::LEAVE_REACTION);
        //$message->react(JoinableChannelMessage::DELETE_REACTION);
        $this->message->delete();
    }
}
