<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Error\RewatchErrorDm;
use App\Message\RewatchNomination;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class AutoValidateSubscriber implements EventSubscriberInterface
{
    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * @var RewatchErrorDm
     */
    private $error;

    /**
     * AutoValidateSubscriber constructor.
     * @param RewatchChannel $rewatch
     * @param RewatchErrorDm $error
     */
    public function __construct(
        RewatchChannel $rewatch,
        RewatchErrorDm $error
    ) {
        $this->rewatch = $rewatch;
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
        if ((int)$message->channel->id !== $this->rewatch->getChannelId()) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        // Invalid message, delet
        if (!RewatchNomination::isContender($message->content)) {
            $message->delete(0, 'Not a valid nomination');

            return;
        }
        // Fetch data
        $nomination = RewatchNomination::fromYasmin($message);
        $nomination->setAnime($this->rewatch->getMal()->loadAnime($nomination->getAnimeId()));
        $errors = $this->rewatch->validate($nomination);
        // Invalid
        if (count($errors)) {
            /** @noinspection PhpToStringImplementationInspection */
            $io->error($nomination->getAuthor().': '.$nomination->getAnime()->title.PHP_EOL.$errors);
            $this->error->send($nomination);
            $message->delete();

            return;
        }
        // Valid, add reaction
        $message->react('🔼');
        $nominationCount = count($this->rewatch->getValidNominations());
        if ($nominationCount !== 10) {
            $io->writeln(sprintf('Not starting yet %s/10 nominations', $nominationCount));

            return;
        }
        // Enough nominees, start it
        $this->rewatch->startVoting($event->getPermissionsRole());
    }
}
