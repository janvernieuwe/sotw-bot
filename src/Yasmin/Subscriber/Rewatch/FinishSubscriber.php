<?php

namespace App\Yasmin\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class FinishSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc rewatch finish';

    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * ValidateSubscriber constructor.
     * @param RewatchChannel $rewatch
     */
    public function __construct(
        RewatchChannel $rewatch
    ) {
        $this->rewatch = $rewatch;
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
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        $nominations = $this->rewatch->getValidNominations();
        try {
            if (count($nominations) !== 10) {
                throw new RuntimeException('Invalid number of nominees '.count($nominations));
            }
            if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
                throw new RuntimeException('There is no clear winner');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->channel->send(':x:'.$e->getMessage());

            return;
        }
        $winner = $nominations[0];
        $io->writeln('Announce winner');
        $this->rewatch->message(
            sprintf(
                ':trophy: Deze rewatch kijken we naar %s (%s), genomineerd door <@!%s>',
                $winner->getAnime()->title,
                $winner->getContent(),
                $winner->getAuthorId()
            )
        );
    }
}
