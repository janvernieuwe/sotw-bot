<?php

namespace App\Yasmin\Subscriber\Cots;

use App\Channel\CotsChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc cots ranking';

    /**
     * @var CotsChannel
     */
    private $cots;

    /**
     * ValidateSubscriber constructor.
     * @param CotsChannel $cots
     */
    public function __construct(
        CotsChannel $cots
    ) {
        $this->cots = $cots;
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
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $nominations = $this->cots->getLastNominations();
        if (!count($nominations)) {
            $message->reply('Er zijn nog geen nominaties');

            return;
        }
        $message->channel->send($this->cots->getTop10());
    }
}
