<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Error\RewatchErrorDm;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class ValidateSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc rewatch validate';

    /**
     * @var string
     */
    private $adminRole;

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
     * ValidateSubscriber constructor.
     * @param string $adminRole
     * @param RewatchChannel $rewatch
     * @param RewatchErrorDm $error
     * @param ValidatorInterface $validator
     */
    public function __construct(
        $adminRole,
        RewatchChannel $rewatch,
        RewatchErrorDm $error,
        ValidatorInterface $validator
    ) {
        $this->adminRole = $adminRole;
        $this->rewatch = $rewatch;
        $this->error = $error;
        $this->validator = $validator;
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
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        if (!$message->member->roles->has((int)$this->adminRole)) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();


        $output = [];
        $message->channel->send('Fetching MAL data');
        $nominations = $this->rewatch->getLastNominations();
        if (count($nominations) !== 10) {
            $output[] = sprintf(':x: Wrong amount of nominations (%s/10)', count($nominations));
        }
        foreach ($nominations as $nomination) {
            $errors = $this->validator->validate($nomination);
            if (!count($errors)) {
                $output[] = ':white_check_mark: '.$nomination->getAuthor().': '.$nomination->getAnime()->title;
                continue;
            }
            $output[] = ':x: '.$nomination->getAuthor().': '.$nomination->getAnime()->title.PHP_EOL.$errors;
            $this->error->send($nomination);
            $this->rewatch->addReaction($nomination, '❌');
        }
        $message->channel->send(implode(PHP_EOL, $output));
    }
}
