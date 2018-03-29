<?php

namespace App\Yasmin\Subscriber;

use App\Channel\SotwChannel;
use App\Error\SotwErrorDm;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class SotwStartSubscriber
 * @package App\Yasmin\Subscriber
 */
class SotwStartSubscriber implements EventSubscriberInterface
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
     * SotwStartSubscriber constructor.
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
        $commandTxt = '!haamc sotw validate ';
        if (strpos($message->content, $commandTxt) !== 0) {
            return;
        }
        if (!$message->member->roles->has((int)$this->adminRole)) {
            return;
        }
        $event->stopPropagation();

        $nominations = $this->sotw->getLastNominations();
        // Check count
        $nominationCount = \count($nominations);
        if ($nominationCount !== 10) {
            $message->reply(sprintf('Wrong amount of nominations (%s/10)', $nominationCount));
        }
        if (count($nominations) >= 2 && $nominations[0]->getVotes() === $nominations[1]->getVotes()) {
            $message->reply('There is no clear winner!');
        }
        foreach ($nominations as $nomination) {
            $errors = $this->sotw->validate($nomination);
            if (\count($errors)) {
                //$errorMessenger->send($nomination);
                $this->sotw->addReaction($nomination, '❌');
                $message->reply($nomination.PHP_EOL.$errors);
                continue;
            }
            $message->reply($nomination);
            $this->sotw->removeReaction($nomination, '❌');
        }
    }
}
