<?php

namespace App\Yasmin\Subscriber;

use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Process;

/**
 * Lets admins run symfony commands
 * Class CommandSubscriber
 * @package App\Yasmin\Subscriber
 */
class CommandSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $adminRole;

    /**
     * CommandSubscriber constructor.
     * @param string $adminRole env(ADMIN_ROLE)
     */
    public function __construct(string $adminRole)
    {
        $this->adminRole = $adminRole;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        $commandTxt = '!haamc console ';
        if (strpos($message->content, $commandTxt) !== 0) {
            return;
        }
        if (!$message->member->roles->has((int)$this->adminRole)) {
            return;
        }
        $event->stopPropagation();
        $command = str_replace($commandTxt, '', $message->content);
        $process = new Process($command = 'php bin/console '.$command.' --no-ansi');
        $process->run();
        $output = $process->getOutput().$process->getErrorOutput();
        $parts = explode(PHP_EOL, $output);
        $parts = array_chunk($parts, 20);
        foreach ($parts as $part) {
            $message->channel->send(implode(PHP_EOL, $part));
        }
    }
}
