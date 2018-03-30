<?php

namespace App\Yasmin\Subscriber;

use App\Channel\SotwChannel;
use App\Error\SotwErrorDm;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class SotwValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class SotwValidateSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $adminRole;

    /**
     * @var SotwChannel
     */
    private $sotw;

    /**
     * @var SotwErrorDm
     */
    private $error;

    /**
     * SotwValidateSubscriber constructor.
     * @param string $adminRole
     * @param SotwChannel $sotw
     * @param SotwErrorDm $error
     */
    public function __construct($adminRole, SotwChannel $sotw, SotwErrorDm $error)
    {
        $this->adminRole = $adminRole;
        $this->sotw = $sotw;
        $this->error = $error;
    }


    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        $commandTxt = '!haamc sotw validate';
        if (strpos($message->content, $commandTxt) !== 0) {
            $event->getIo()->writeln('Did not match');

            return;
        }
        if (!$message->member->roles->has((int)$this->adminRole)) {
            $event->getIo()->writeln('No admin role');

            return;
        }
        $event->stopPropagation();

        $nominations = $this->sotw->getLastNominations();
        $output = [];
        // Check count
        $nominationCount = \count($nominations);
        if ($nominationCount !== 10) {
            $output[] = sprintf(':x: Wrong amount of nominations (%s/10)', $nominationCount);
        }
        if (count($nominations) >= 2 && $nominations[0]->getVotes() === $nominations[1]->getVotes()) {
            $output[] = ':x: There is no clear winner!';
        }
        foreach ($nominations as $nomination) {
            $errors = $this->sotw->validate($nomination);
            if (\count($errors)) {
                $this->error->send($nomination);
                $this->sotw->addReaction($nomination, '❌');
                $output[] = ':x: '.$nomination.PHP_EOL.$errors;
                continue;
            }
            $output[] = ':white_check_mark:  '.$nomination;
            $this->sotw->removeReaction($nomination, '❌');
        }
        $message->channel->send(implode(PHP_EOL, $output));
    }
}
