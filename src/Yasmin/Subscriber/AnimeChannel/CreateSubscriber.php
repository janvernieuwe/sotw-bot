<?php

namespace App\Yasmin\Subscriber\AnimeChannel;

use App\Channel\Channel;
use App\Message\JoinableChannelMessage;
use App\MyAnimeList\MyAnimeListClient;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Guild;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Jikan\Model\Anime;
use Symfony\Component\Console\Style\SymfonyStyle;
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
     * @var int
     */
    protected $everyoneRole;

    /**
     * @var int
     */
    private $parent;

    /**
     * @var TextChannel
     */
    private $textChannel;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var MyAnimeListClient
     */
    private $mal;

    /**
     * CreateSubscriber constructor.
     * @param MyAnimeListClient $mal
     * @param int $everyoneRole
     */
    public function __construct(
        MyAnimeListClient $mal,
        int $everyoneRole
    ) {
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
        $this->io = $io = $event->getIo();
        $this->message = $message = $event->getMessage();
        /** @var Client client */
        $this->client = $event->getMessage()->client;
        $matchCommand = preg_match('/^(\!haamc channel )(\d+)\s([\S]*)\s?(.*)$/', $message->content, $name);
        if (!$matchCommand || !$event->isAdmin()) {
            return;
        }
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $this->parent = (int)$name[2];
        $this->link = $name[4];
        $this->anime = $this->mal->loadAnime(MyAnimeListClient::getAnimeId($this->link));
        $name = $name[3];
        $guild = $message->guild;
        $this->createChannel($guild, $name);
    }

    /**
     * @param Guild $guild
     * @param string $name
     */
    protected function createChannel(Guild $guild, string $name)
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
                        'id'    => $this->client->user->id,
                        'allow' => Channel::ROLE_VIEW_MESSAGES,
                        'type'  => 'member',
                    ],
                ],
                'parent'               => $this->parent,
                'nsfw'                 => false,
            ]
        )->done(
            function (TextChannel $channel) {
                $this->textChannel = $channel;
                $channel->setTopic(sprintf('%s || %s', $this->anime->title, $this->link));
                $channel->send(
                    sprintf(
                        ":tv: Hoi iedereen! In dit channel kijken we naar **%s**.\n%s",
                        $this->anime->title,
                        $this->link
                    )
                );
                $this->sendJoinMessage($channel);
            }
        );
    }

    /**
     * @param TextChannel $channel
     */
    public function sendJoinMessage(TextChannel $channel)
    {
        $parts = parse_url($this->link);
        $query = parse_query($parts['query'] ?? '');
        $query['c'] = $channel->id;
        if (!preg_match('#https?://myanimelist.net/anime/\d+#', $this->link, $link)) {
            return;
        }
        $link = $link[0];
        $link .= '?'.http_build_query($query);
        $embed = JoinableChannelMessage::generateRichChannelMessage($this->anime, (int)$channel->id, $link);
        $this->message->channel
            ->send(JoinableChannelMessage::TEXT_MESSAGE, $embed)
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
        $this->io->success(sprintf('Anime channel #%s created for %s.', $this->textChannel->name, $this->anime->title));
    }
}
