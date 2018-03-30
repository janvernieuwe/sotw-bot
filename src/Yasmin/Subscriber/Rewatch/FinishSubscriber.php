<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Channel\RewatchChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc rewatch finish';

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
        $message->channel->send('Open nominations message');
        $this->rewatch->message('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');

        $nominations = $this->rewatch->getValidNominations();
        try {
            if (count($nominations) !== 10) {
                throw new RuntimeException('Invalid number of nominees '.count($nominations));
            }
            if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
                throw new RuntimeException('There is no clear winner');
            }
        } catch (RuntimeException $e) {
            $event->getIo()->error($e->getMessage());
            $message->channel->send(':x:'.$e->getMessage());

            return;
        }
        $winner = $nominations[0];
        $message->channel->send('Announce winner');
        $this->rewatch->message(
            sprintf(
                ':trophy: Deze rewatch kijken we naar %s (%s), genomineerd door <@!%s>',
                $winner->getAnime()->title,
                $winner->getContent(),
                $winner->getAuthorId()
            )
        );
    }
}
