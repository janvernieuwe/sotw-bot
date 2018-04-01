<?php

namespace App\Yasmin\Subscriber\Cots;

use App\Channel\CotsChannel;
use App\Error\CotsErrorDm;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class AutoValidateSubscriber implements EventSubscriberInterface
{
    public const LIMIT = 25;

    /**
     * @var CotsErrorDm
     */
    private $error;

    /**
     * @var CotsChannel
     */
    private $cots;

    /**
     * @var string
     */
    private $season;

    /**
     * AutoValidateSubscriber constructor.
     * @param CotsChannel $character
     * @param CotsErrorDm $error
     * @param string $season
     * @internal param RewatchChannel $rewatch
     */
    public function __construct(
        CotsChannel $character,
        CotsErrorDm $error,
        string $season
    ) {
        $this->error = $error;
        $this->cots = $character;
        $this->season = $season;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * 25 max
     * unique
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->cots->getChannelId()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        // Attempt to load the nomination
        try {
            $nomination = $this->cots->loadNomination($message);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            $message->delete();

            return;
        }
        // Set the season for validation
        $nomination->setSeason($this->season);
        // Validate the nomination
        if (!$this->error->isValid($nomination)) {
            $this->error->send($nomination, $this->season);
            $io->error(implode(PHP_EOL, $this->error->getErrorArray($nomination)).PHP_EOL.$message->content);
            $message->delete();

            return;
        }
        // Success
        $message->react('🔼');
        $io->success($nomination->getCharacter()->name.' - '.$nomination->getAnime()->title);
        // Check total nominations
        $nominations = $this->cots->getLastNominations();
        $nominationCount = count($nominations);
        if ($nominationCount !== self::LIMIT) {
            $io->writeln(sprintf('Not locking yet %s/%s nominations', $nominationCount, self::LIMIT));

            return;
        }
        // Close channel when limit is reached
        $this->cots->closeNominations();
        $io->success('Closed nominations');
    }
}
