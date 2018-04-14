<?php

namespace App\Yasmin\Subscriber\Bikkel;

use App\Entity\Bikkel;
use App\Yasmin\Event\MessageReceivedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Display the current bikkel ranking
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc bikkel ranking';

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * RankingSubscriber constructor.
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
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
        if ($message->content !== self::COMMAND) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        $messages = [];
        $bikkels = $this->doctrine
            ->getRepository(Bikkel::class)
            ->findTop10();

        foreach ($bikkels as $i => $bikkel) {
            $messages[] = sprintf(
                '#%s **%s** met **%s** punten',
                $i + 1,
                $bikkel->getDisplayName(),
                $bikkel->getPoints()
            );
        }
        $message->channel->send(
            ':last_quarter_moon_with_face: **HAAMC Discord bikkel ranking** *top 10*'.PHP_EOL.implode(
                PHP_EOL,
                $messages
            )
        );
        $io->success('Ranking displayed');
    }
}
