<?php

namespace App\Yasmin\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Formatter\BBCodeFormatter;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class StartSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc sotw';

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
        $io = $event->getIo();

        $message->channel->startTyping();
        $nominations = $this->sotw->getLastNominations();
        try {
            $this->sotw->validateNominees($nominations);
            if (!\count($nominations)) {
                throw new RuntimeException('No nominations found');
            }
            if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
                throw new RuntimeException('There is no clear winner!');
            }
        } catch (RuntimeException $e) {
            $message->channel->send(':x: '.$e->getMessage());
            $io->error($e->getMessage());
            $message->channel->stopTyping();

            return;
        }

        // Announce the winner and unlock the channel
        $winner = $nominations[0];
        $io->writeln((string)$winner);
        $this->sotw->announceWinner($winner);
        $this->sotw->addMedals($nominations);
        $this->sotw->openNominations();

        // Output post for the forum
        $formatter = new BBCodeFormatter($nominations);
        $bbcode = '```'.$formatter->createMessage().'```';
        $message->channel->send($bbcode);
        $message->channel->stopTyping();
    }
}
