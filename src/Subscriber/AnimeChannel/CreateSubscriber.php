<?php

namespace App\Subscriber\AnimeChannel;

use App\Channel\AnimeChannelCreator;
use App\Context\CreateAnimeChannelContext;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Message;
use Jikan\Helper\Parser;
use Jikan\Model\Anime\Anime;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
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
     * @var MalClient
     */
    private $mal;

    /**
     * @var AnimeChannelCreator
     */
    private $channelCreator;

    /**
     * CreateSubscriber constructor.
     *
     * @param MalClient           $mal
     * @param int                 $everyoneRole
     * @param AnimeChannelCreator $channelCreator
     */
    public function __construct(
        MalClient $mal,
        int $everyoneRole,
        AnimeChannelCreator $channelCreator
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
        $matchCommand = preg_match('/^(\!haamc channel )([\S]*)\s?(.*)$/', $message->content, $name);
        if (!$matchCommand || !$event->isAdmin()) {
            return;
        }
        $this->io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $link = $name[3];
        $animeId = Parser::idFromUrl($link);
        $anime = $this->mal->getAnime(new AnimeRequest($animeId));
        $channelName = $name[2];

        // Create context
        /** @noinspection PhpUndefinedFieldInspection */
        $context = new CreateAnimeChannelContext(
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
        $io->success(sprintf('Anime channel %s created for %s', $channelName, $anime->getTitle()));
        $message->delete();
    }
}
