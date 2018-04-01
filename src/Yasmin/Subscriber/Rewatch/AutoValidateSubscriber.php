<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Error\RewatchErrorDm;
use App\Exception\RuntimeException;
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

        try {
            if (!RewatchNomination::isContender($message->content)) {
                throw new RuntimeException('Not a contender');
            }
            $nomination = RewatchNomination::fromYasmin($message);
            try {
                $anime = $this->rewatch->getMal()->loadAnime($nomination->getAnimeId());
            } catch (\Exception $e) {
                throw new RuntimeException('Invalid anime link');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->delete();

            return;
        }

        $nomination->setAnime($anime);
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
        $io->success($nomination->getAnime()->title);
        $nominationCount = count($this->rewatch->getValidNominations());
        if ($nominationCount !== 10) {
            $io->writeln(sprintf('Not starting yet %s/10 nominations', $nominationCount));

            return;
        }
        // Enough nominees, start it
        $this->rewatch->closeNominations($event->getPermissionsRole());
        $io->success('Closed nominations');
    }
}
