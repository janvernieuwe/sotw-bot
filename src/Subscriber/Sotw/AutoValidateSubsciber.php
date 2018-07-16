<?php

namespace App\Subscriber\Sotw;

use App\Channel\Channel;
use App\Channel\SongOfTheWeekChannel;
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
    private $everyoneRole;

    /**
     * @var int
     */
    private $sotwChannelId;

    /**
     * AutoValidateSubsciber constructor.
     *
     * @param ValidatorInterface $validator
     * @param int                $everyoneRole
     * @param int                $sotwChannelId
     */
    public function __construct(ValidatorInterface $validator, int $everyoneRole, int $sotwChannelId)
    {
        $this->validator = $validator;
        $this->everyoneRole = $everyoneRole;
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
            //$this->error->send($nomination); // @TODO redo errors
            $message->delete();
            /** @noinspection PhpToStringImplementationInspection */
            $io->error($nomination.PHP_EOL.$errors);

            return;
        }
        $message->react('🔼');
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
                $channel = $message->guild->channels->get($message->channel->id);
                $channel->overwritePermissions(
                    $this->everyoneRole,
                    0,
                    Channel::ROLE_SEND_MESSAGES,
                    'Song of the week nominations closed'
                );
                $io->success('Closed nominations');
            }
        );
    }
}
