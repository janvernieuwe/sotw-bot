<?php

namespace App\Subscriber\SimpleChannel;

use App\Channel\SimpleChannelCreator;
use App\Command\CommandParser;
use App\Context\CreateSimpleChannelContext;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class CreateSubscriber implements EventSubscriberInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var int
     */
    protected $everyoneRole;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * CreateSubscriber constructor.
     *
     * @param int                  $everyoneRole
     */
    public function __construct(
        int $everyoneRole
    ) {
        $this->everyoneRole = $everyoneRole;
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
        $this->message = $message = $event->getMessage();
        $parsedMessage = new CommandParser($message);
        /** @var Client client */
        $matchCommand = preg_match('/^(\!haamc simplechannel )([\S]*)\s?(.*)$/', $parsedMessage, $name);
        if (!$matchCommand || !$event->isAdmin()) {
            return;
        }
        $this->io = $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        [$cmd, $cmd, $channelName, $description] = $name;

        // Create context
        $context = new CreateSimpleChannelContext(
            $parsedMessage->getCategoryId() ?? (int)$message->channel->parentID,
            $channelName,
            $description,
            (int)$this->everyoneRole,
            $message->guild,
            $message->client,
            $message->channel
        );
        $channelCreator = new SimpleChannelCreator($context);
        // Create channel from context
        $channelCreator->create($context);
        $io->success(sprintf('Simple joinable channel %s created for %s', $channelName, $description));
        $message->delete();
    }
}
