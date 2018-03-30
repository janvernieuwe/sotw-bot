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
class VoteSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc rewatch vote';

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

        $message->channel->send('Fetch nomination data');
        $nominations = $this->rewatch->getValidNominations();
        if (count($nominations) !== 10) {
            throw new \RuntimeException('Invalid number of nominees '.count($nominations));
        }
        $message->channel->send('Set channel permissions');
        $this->rewatch->deny($this->permissionRole, Channel::ROLE_SEND_MESSAGES);
        $message->channel->send('Add reactions');
        foreach ($nominations as $nomination) {
            $this->rewatch->addReaction($nomination, '🔼');
        }
        $message->channel->send('Send message');
        $this->rewatch->message('Laat het stemmen beginnen :checkered_flag: Enkel stemmen als je mee wil kijken!');
        $this->rewatch->message('We maken de winnaar zondag namiddag bekend.');
    }
}
