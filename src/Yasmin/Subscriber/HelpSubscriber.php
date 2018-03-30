<?php

namespace App\Yasmin\Subscriber;

use App\Channel\SotwChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class HelpSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc help';

    /**
     * @var int
     */
    private $adminRole;

    /**
     * HelpSubscriber constructor.
     * @param int $adminRole
     */
    public function __construct($adminRole)
    {
        $this->adminRole = $adminRole;
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

        $help = <<<HELP
```        
All commands are prefixed with !haamc

!haamc <section> <action>

## sotw (Song of the week) ##
sotw next           (start the next round of song of the week, admins only)
sotw ranking        (show the current ranking)

## rewatch (Anime Rewatch) ###
rewatch start       (start the next rewatch round, admins only)
rewatch finish      (finish the rewatch round, admins only)
rewatch ranking     (show the current ranking)
```
HELP;
        $message->channel->send($help);
    }
}
