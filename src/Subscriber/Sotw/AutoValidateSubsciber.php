<?php

namespace App\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Event\MessageReceivedEvent;
use App\Message\SotwNomination;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class AutoValidateSubsciber implements EventSubscriberInterface
{
    /**
     * @var SotwChannel
     */
    private $sotw;

    /**
     * ValidateSubscriber constructor.
     *
     * @param SotwChannel $sotw
     */
    public function __construct(SotwChannel $sotw)
    {
        $this->sotw = $sotw;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [];
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
        $io->success('Closed nominations');
    }
}
