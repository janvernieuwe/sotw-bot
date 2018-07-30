<?php

namespace App\Subscriber\Sotw;

use App\Channel\Channel;
use App\Channel\SongOfTheWeekChannel;
use App\Entity\Reaction;
use App\Error\Messenger;
use App\Event\MessageReceivedEvent;
use App\Message\SotwNomination;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class AutoValidateSubsciber implements EventSubscriberInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var int
     */
    private $roleId;

    /**
     * @var int
     */
    private $sotwChannelId;

    /**
     * AutoValidateSubsciber constructor.
     *
     * @param ValidatorInterface $validator
     * @param int                $roleId
     * @param int                $sotwChannelId
     */
    public function __construct(ValidatorInterface $validator, int $roleId, int $sotwChannelId)
    {
        $this->validator = $validator;
        $this->roleId = $roleId;
        $this->sotwChannelId = $sotwChannelId;
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
        if ((int)$message->channel->id !== $this->sotwChannelId) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        $nomination = SotwNomination::fromMessage($message);
        $errors = $this->validator->validate($nomination);
        if (\count($errors)) {
            (new Messenger($message, $errors, $io))->send();

            return;
        }
        $message->react(Reaction::VOTE);
        $sotw = new SongOfTheWeekChannel($message->channel);
        $sotw->getNominations(
            function (array $nominations) use ($io, $message) {
                $nominationCount = count($nominations);
                if ($nominationCount !== 10) {
                    $io->writeln(sprintf('Not starting yet %s/10 nominations', $nominationCount));

                    return;
                }
                /** @var GuildChannelInterface $channel */
                $message->channel->send('Laat het stemmen beginnen! :checkered_flag:');
                Channel::close($message->channel, $this->roleId);
                $io->success('Closed nominations');
            }
        );
    }
}
