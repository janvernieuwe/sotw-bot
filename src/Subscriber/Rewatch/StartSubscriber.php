<?php

namespace App\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class StartSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc rewatch start';

    /**
     * @var int
     */
    private $rewatchChannelId;

    /**
     * StartSubscriber constructor.
     *
     * @param int       $rewatchChannelId
     */
    public function __construct(int $rewatchChannelId)
    {
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
        $message = $event->getMessage();
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        /** @var TextChannel $channel */
        $channel = $message->guild->channels->get($this->rewatchChannelId);
        $channel->send('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');
        Channel::open($channel, $event->getPermissionsRole());
        $io->success('Opened nominations');
    }
}
