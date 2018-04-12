<?php

namespace App\Yasmin\Subscriber\Bikkel;

use App\Entity\Bikkel;
use App\Repository\BikkelRepository;
use App\Util\Util;
use App\Yasmin\Event\MessageReceivedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class BikkelSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!bikkelpunt';

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var BikkelRepository
     */
    private $bikkelRepo;

    /**
     * BikkelSubscriber constructor.
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->bikkelRepo = $doctrine->getRepository(Bikkel::class);
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

        if (!$this->isValidHour()) {
            $message->reply('Probeer nog eens tussen 03:00 en 05:00');

            return;
        }
        if ($this->hasCooldown($message->author->id)) {
            $message->reply('Je hebt al gebikkelt vandaag, probeer het morgen nog eens');

            return;
        }
        $bikkel = $this->getBikkel($message->author->id);
        $points = $bikkel->addPoint();
        $bikkel->setLastUpdate(Util::getCurrentDate());
        $this->doctrine->flush();
        $message->reply(sprintf('Je bent een echte bikkel! **+1** (**%s** punten totaal)', $points));
    }

    /**
     * @param int $memberId
     * @return Bikkel
     */
    private function getBikkel(int $memberId): Bikkel
    {
        $bikkel = $this->bikkelRepo->findOneBy(['memberId' => $memberId]);
        if ($bikkel instanceof Bikkel) {
            return $bikkel;
        }
        $bikkel = new Bikkel();
        $bikkel->setMemberId($memberId);
        $this->doctrine->persist($bikkel);

        return $bikkel;
    }

    /**
     * @return bool
     */
    private function isValidHour(): bool
    {
        $time = Util::getCurrentDate();
        $hour = (int)$time->format('H');

        return 3 <= $hour && $hour < 5;
    }

    /**
     * @param int $memberId
     * @return bool
     */
    private function hasCooldown(int $memberId): bool
    {
        if (!$member = $this->bikkelRepo->findOneBy(['memberId' => $memberId])) {
            return false;
        }
        if ($member->getLastUpdate() === null) {
            return false;
        }
        $today = Util::getCurrentDate();

        return $member->getLastUpdate()->format('Y-m-d') === $today->format('Y-m-d');
    }
}
