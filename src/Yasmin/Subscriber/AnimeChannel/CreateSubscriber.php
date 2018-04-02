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
    protected $seasonalAnime;
    /**
     * @var int
     */
    protected $everyoneRole;
    /**
     * @var MyAnimeListClient
     */
    private $mal;

    /**
     * CreateSubscriber constructor.
     * @param SeasonalAnimeChannel $channel
     * @param MyAnimeListClient $mal
     * @param int $seasonalAnime
     * @param int $everyoneRole
     */
    public function __construct(
        SeasonalAnimeChannel $channel,
        MyAnimeListClient $mal,
        int $seasonalAnime,
        int $everyoneRole
    ) {
        $this->channel = $channel;
        $this->seasonalAnime = $seasonalAnime;
        $this->everyoneRole = $everyoneRole;
        $this->mal = $mal;
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
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->seasonalAnime || !$event->isAdmin()) {
            return;
        }
        if (!preg_match('/(\!haamc create\-channel )([\S]*)\s?(.*)$/', $message->content, $name)) {
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
                'topic'                => sprintf('%s channel [%s]', $this->anime->title, $this->link),
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
                'parent'               => 430306918561611788,
                'nsfw'                 => false,
            ]
        )->done(
            function (TextChannel $channel) use ($role) {
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
            "Kijk nu mee naar *%s*\nChannel: %s\nAnime: %s",
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
