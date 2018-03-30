<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Channel\RewatchChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class NominateSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc rewatch nominate';

    /**
     * @var int
     */
    private $adminRole;

    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * @var int
     */
    private $permissionRole;

    /**
     * ValidateSubscriber constructor.
     * @param int|string $adminRole
     * @param int $permissionRole
     * @param RewatchChannel $rewatch
     */
    public function __construct(
        int $adminRole,
        int $permissionRole,
        RewatchChannel $rewatch
    ) {
        $this->adminRole = $adminRole;
        $this->rewatch = $rewatch;
        $this->permissionRole = $permissionRole;
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
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        if (!$message->member->roles->has((int)$this->adminRole)) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $message->channel->send('Set permissions');
        $this->rewatch->allow($this->permissionRole, Channel::ROLE_SEND_MESSAGES);
        $message->channel->send('Send message');
        $this->rewatch->message('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');
    }
}
