<?php

namespace App\Subscriber\Cots;

use App\Channel\CotsChannel;
use App\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class StartSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc cots start';

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
        return [];
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
        $this->cots->openChannel($this->season);
        $io->success('Opened nominations');
    }
}
