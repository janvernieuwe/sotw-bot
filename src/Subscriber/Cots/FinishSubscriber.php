<?php

namespace App\Subscriber\Cots;

use App\Channel\CotsChannel;
use App\Event\MessageReceivedEvent;
use App\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc cots finish';

    /**
     * @var CotsChannel
     */
    private $cots;

    /**
     * @var string
     */
    private $season;

    /**
     * ValidateSubscriber constructor.
     *
     * @param CotsChannel $cots
     * @param string      $season
     *
     * @internal param RewatchChannel $rewatch
     */
    public function __construct(
        CotsChannel $cots,
        string $season
    ) {
        $this->cots = $cots;
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
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $nominations = $this->cots->getLastNominations();
        $nominationCount = count($nominations);
        try {
            if ($nominationCount <= 2) {
                throw new RuntimeException('Not enough nominees');
            }
            if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
                throw new RuntimeException('There is no clear winner');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->reply(':x: '.$e->getMessage());

            return;
        }
        $this->cots->message($this->cots->getTop10());
        $io->success('Displayed top 10');
        $this->cots->announceWinner($nominations[0], $this->season);
        $io->success('Announced the winner');
    }
}
