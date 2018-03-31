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
    /**
     * @var CotsErrorDm
     */
    private $error;

    /**
     * @var CotsChannel
     */
    private $character;

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
        $this->character = $character;
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
        if ((int)$message->channel->id !== $this->character->getChannelId()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        try {
            $nomination = $this->character->loadNomination($message);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            $message->delete();

            return;
        }

        // Set the season for validation
        $nomination->setSeason($this->season);
        if (!$this->error->isValid($nomination)) {
            $this->error->send($nomination, $this->season);
            $io->error(implode(PHP_EOL, $this->error->getErrorArray($nomination)).PHP_EOL.$message->content);
            $message->delete();

            return;
        }
        // Validate the nomination
        $message->react('🔼');
        $io->success($nomination->getCharacter()->name.' - '.$nomination->getAnime()->title);
    }
}
