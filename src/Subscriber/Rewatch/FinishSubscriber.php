<?php

namespace App\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Jikan\MyAnimeList\MalClient;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc rewatch finish';

    /**
     * @var MalClient
     */
    private $jikan;

    /**
     * @var MessageReceivedEvent
     */
    private $event;

    /**
     * @var int
     */
    private $rewatchChannelId;

    /**
     * ValidateSubscriber constructor.
     *
     * @param MalClient      $jikan
     * @param int            $rewatchChannelId
     */
    public function __construct(
        MalClient $jikan,
        int $rewatchChannelId
    ) {
        $this->jikan = $jikan;
        $this->rewatchChannelId = $rewatchChannelId;
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
        $this->event = $event;
        $message = $event->getMessage();
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $rewatch = new RewatchChannel($message->channel, $this->jikan);
        $rewatch->getNominations()
            ->then(\Closure::fromCallable([$this, 'onMessagesLoaded']));
    }

    /**
     * @param array $nominations
     */
    private function onMessagesLoaded(array $nominations): void
    {
        $message = $this->event->getMessage();
        $io = $this->event->getIo();
        try {
            if (!count($nominations)) {
                throw new RuntimeException('Invalid number of nominees '.count($nominations));
            }
            if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
                throw new RuntimeException('There is no clear winner');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->channel->send(':x:'.$e->getMessage());

            return;
        }
        $winner = $nominations[0];
        $io->writeln('Announce winner');
        /** @var TextChannel $rewatchChannel */
        $rewatchChannel = $message->client->channels->get($this->rewatchChannelId);
        $rewatchChannel->send(
            sprintf(
                ':trophy: Deze rewatch kijken we naar %s (%s), genomineerd door <@!%s>',
                $winner->getAnime()->title,
                $winner->getContent(),
                $winner->getAuthorId()
            )
        );
        $io->success('Annouced the winner');
    }
}
