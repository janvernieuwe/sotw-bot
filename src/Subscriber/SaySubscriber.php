<?php

namespace App\Subscriber;

use App\Event\MessageReceivedEvent;
use RestCord\DiscordClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class SaySubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc say';

    /**
     * @var int
     */
    private $adminRole;
    /**
     * @var DiscordClient
     */
    private $discord;

    /**
     * AdminHelpSubscriber constructor.
     *
     * @param int           $adminRole
     * @param DiscordClient $discord
     */
    public function __construct(int $adminRole, DiscordClient $discord)
    {
        $this->adminRole = $adminRole;
        $this->discord = $discord;
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
        if (!$message->member->roles->has($this->adminRole)) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        if (!preg_match('/^\!haamc say (\d+) (.*)$/', $message->content, $cmd)) {
            $io->writeln(sprintf('Wrong format: %s', $message->content));

            return;
        }
        $this->discord->channel->createMessage(
            [
                'channel.id' => (int)$cmd[1],
                'content'    => $cmd[2],
            ]
        );
        $io->success(
            sprintf('Sent message to channel %s, %s by %s', $cmd[1], $cmd[2], $message->author->username)
        );
    }
}
