<?php

namespace App\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Channel\RewatchChannel;
use App\Event\MessageReceivedEvent;
use App\Message\RewatchNomination;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
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
     * @var int
     */
    private $roleId;

    /**
     * ValidateSubscriber constructor.
     *
     * @param MalClient $jikan
     * @param int       $rewatchChannelId
     * @param int       $roleId
     */
    public function __construct(
        MalClient $jikan,
        int $rewatchChannelId,
        int $roleId
    ) {
        $this->jikan = $jikan;
        $this->rewatchChannelId = $rewatchChannelId;
        $this->roleId = $roleId;
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
        $rewatch = new RewatchChannel($message->client->channels->get($this->rewatchChannelId), $this->jikan);
        $rewatch->getNominations()
            ->then(\Closure::fromCallable([$this, 'onMessagesLoaded']));
    }

    /**
     * @param RewatchNomination[] $nominations
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
                $winner->getAnime()->getTitle(),
                $winner->getAnime()->getUrl(),
                $winner->getAuthorId()
            )
        );
        $io->success('Annouced the winner');
        /** @var GuildChannelInterface $guildChannel */
        $guildChannel = $message->guild->channels->get($this->rewatchChannelId);
        $guildChannel->overwritePermissions(
            $this->roleId,
            Channel::ROLE_VIEW_MESSAGES,
            Channel::ROLE_SEND_MESSAGES,
            'Closed nominations'
        );
        $io->success('Closed channel');
    }
}
