<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Error\RewatchErrorDm;
use App\Message\RewatchNomination;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class AutoValidateSubscriber implements EventSubscriberInterface
{
    /**
     * @var int
     */
    private $channelId;

    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * @var RewatchErrorDm
     */
    private $error;

    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var int
     */
    private $roleId;

    /**
     * AutoValidateSubscriber constructor.
     * @param int $channelId
     * @param int $roleId
     * @param RewatchChannel $rewatch
     * @param RewatchErrorDm $error
     * @param ValidatorInterface $validator
     */
    public function __construct(
        int $channelId,
        int $roleId,
        RewatchChannel $rewatch,
        RewatchErrorDm $error,
        ValidatorInterface $validator
    ) {
        $this->channelId = $channelId;
        $this->rewatch = $rewatch;
        $this->error = $error;
        $this->validator = $validator;
        $this->roleId = $roleId;
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
        if ((int)$message->channel->id !== $this->channelId) {
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
        $nomination->setAnime($this->rewatch->loadAnime($nomination->getAnimeId()));
        $this->validator->validate($nomination);
        $errors = $this->validator->validate($nomination);
        // Invalid
        if (count($errors)) {
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
        $this->rewatch->startVoting($this->roleId);
    }
}
