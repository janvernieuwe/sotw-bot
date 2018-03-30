<?php

namespace App\Yasmin\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Error\SotwErrorDm;
use App\Message\SotwNomination;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class AutoValidateSubsciber implements EventSubscriberInterface
{
    /**
     * @var SotwChannel
     */
    private $sotw;

    /**
     * @var SotwErrorDm
     */
    private $error;

    /**
     * @var int
     */
    private $channelId;

    /**
     * ValidateSubscriber constructor.
     * @param int $channelId
     * @param SotwChannel $sotw
     * @param SotwErrorDm $error
     */
    public function __construct(int $channelId, SotwChannel $sotw, SotwErrorDm $error)
    {
        $this->sotw = $sotw;
        $this->error = $error;
        $this->channelId = $channelId;
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
        if ((int)$message->channel->id !== $this->channelId) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        $nomination = SotwNomination::fromYasmin($message);
        $errors = $this->sotw->validate($nomination);
        if (\count($errors)) {
            $this->error->send($nomination);
            $message->delete();
            $io->error($nomination.PHP_EOL.$errors);

            return;
        }
        $message->react('🔼');
        $nominationCount = count($this->sotw->getLastNominations());
        if ($nominationCount !== 10) {
            $io->writeln(sprintf('Not starting yet %s/10 nominations', $nominationCount));

            return;
        }
        $this->sotw->closeNominations();
    }
}
