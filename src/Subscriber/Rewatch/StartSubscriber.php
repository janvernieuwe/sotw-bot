<?php

namespace App\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Channel\RewatchChannel;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Permissions;
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
    const COMMAND = '!haamc rewatch start';

    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * ValidateSubscriber constructor.
     *
     * @param RewatchChannel $rewatch
     */
    public function __construct(
        RewatchChannel $rewatch
    ) {
        $this->rewatch = $rewatch;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [];
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
        $channel = $message->channel->guild->channels->get($this->rewatch->getChannelId());
        $channel->send('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');
        $permissions = new Permissions();
        $permissions->add(Channel::ROLE_VIEW_MESSAGES);
        $permissions->add(Channel::ROLE_SEND_MESSAGES);
        $channel->overwritePermissions(
            $event->getPermissionsRole(),
            $permissions,
            0,
            'Open nominations'
        );
        $io->success('Opened nominations');
    }
}
