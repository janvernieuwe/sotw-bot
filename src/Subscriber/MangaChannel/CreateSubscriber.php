<?php

namespace App\Subscriber\MangaChannel;

use App\Channel\MangaChannelCreator;
use App\Context\CreateMangaChannelContext;
use App\Event\MessageReceivedEvent;
use App\MyAnimeList\MyAnimeListClient;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Message;
use Jikan\Model\Anime;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
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
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var MyAnimeListClient
     */
    private $mal;

    /**
     * @var MangaChannelCreator
     */
    private $channelCreator;

    /**
     * CreateSubscriber constructor.
     *
     * @param MyAnimeListClient   $mal
     * @param int                 $everyoneRole
     * @param MangaChannelCreator $channelCreator
     */
    public function __construct(
        MyAnimeListClient $mal,
        int $everyoneRole,
        MangaChannelCreator $channelCreator
    ) {
        $this->everyoneRole = $everyoneRole;
        $this->mal = $mal;
        $this->channelCreator = $channelCreator;
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
        $this->message = $message = $event->getMessage();
        /** @var Client client */
        $matchCommand = preg_match('/^(\!haamc mangachannel )([\S]*)\s?(.*)$/', $message->content, $name);
        if (!$matchCommand || !$event->isAdmin()) {
            return;
        }
        $this->io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $link = $name[3];
        $anime = $this->mal->loadManga(MyAnimeListClient::getIdFromUrl($link));
        $channelName = $name[2];

        // Create context
        $context = new CreateMangaChannelContext(
            $anime,
            (int)$message->channel->parentID,
            $channelName,
            $this->everyoneRole,
            $message->guild,
            $message->client,
            $message->channel
        );
        // Create channel from context
        $this->channelCreator->create($context);
        $io->success(sprintf('Manga channel %s created for %s', $channelName, $anime->title));
        $message->delete();
    }
}
