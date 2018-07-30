<?php

namespace App\Subscriber\Admin;

use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Role;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class RolesSubscriber
 *
 * @package App\Subscriber\Admin
 */
class RolesSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc roles';

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

        $roles = $message->guild->roles->all();
        $roles = array_map(
            function (Role $role) {
                return sprintf('%s: %s', $role->id, $role->name);
            },
            $roles
        );
        $message->channel->send(PHP_EOL.implode(PHP_EOL, $roles));

        $io->success('Displayed roles');
    }
}
