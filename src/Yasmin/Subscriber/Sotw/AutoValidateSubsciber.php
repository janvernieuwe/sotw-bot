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
     * ValidateSubscriber constructor.
     * @param SotwChannel $sotw
     * @param SotwErrorDm $error
     */
    public function __construct(SotwChannel $sotw, SotwErrorDm $error)
    {
        $this->sotw = $sotw;
        $this->error = $error;
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
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->sotw->getChannelId()) {
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
            /** @noinspection PhpToStringImplementationInspection */
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
