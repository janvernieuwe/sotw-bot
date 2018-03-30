<?php

namespace App\Yasmin\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class VoteSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc sotw vote';

    /**
     * @var string
     */
    private $adminRole;

    /**
     * @var SotwChannel
     */
    private $sotw;

    /**
     * ValidateSubscriber constructor.
     * @param string $adminRole
     * @param SotwChannel $sotw
     */
    public function __construct($adminRole, SotwChannel $sotw)
    {
        $this->adminRole = $adminRole;
        $this->sotw = $sotw;
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

        $nominations = $this->sotw->getLastNominations();
        try {
            $this->sotw->validateNominees($nominations);
        } catch (RuntimeException $e) {
            $message->channel->send(':x: '.$e->getMessage());

            return;
        }

        // Check amount of nominations
        $nominationCount = \count($nominations);
        if ($nominationCount !== 10) {
            $error = sprintf(':x: Wrong amount of nominations (%s/10)', $nominationCount);
            $message->channel->send($error);
            $event->getIo()->error($error);

            return;
        }
        $event->getIo()->note('Closing nominations');
        $message->channel->send('Closing nominations');
        $this->sotw->closeNominations();

        $event->getIo()->note('Adding reactions');
        $message->channel->send('Adding reactions');
        foreach ($nominations as $nominee) {
            if ($this->sotw->isValid($nominee)) {
                $this->sotw->addReaction($nominee, '🔼');
            }
        }
    }
}
