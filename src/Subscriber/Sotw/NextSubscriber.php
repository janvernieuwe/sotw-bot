<?php

namespace App\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Entity\SotwWinner;
use App\Event\MessageReceivedEvent;
use App\Exception\RuntimeException;
use App\Formatter\BBCodeFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class NextSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc sotw next';

    /**
     * @var SotwChannel
     */
    private $sotw;

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * ValidateSubscriber constructor.
     *
     * @param SotwChannel            $sotw
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(SotwChannel $sotw, EntityManagerInterface $doctrine)
    {
        $this->sotw = $sotw;
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
        if ($message->content !== self::COMMAND || !$event->isAdmin()) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

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

            return;
        }

        $winner = $nominations[0];

        // Add the winner to the database
        $sotwWinner = new SotwWinner();
        $sotwWinner->setMemberId($message->author->id);
        $sotwWinner->setAnime($winner->getAnime());
        $sotwWinner->setArtist($winner->getArtist());
        $sotwWinner->setTitle($winner->getTitle());
        $sotwWinner->setDisplayName($message->author->username);
        $sotwWinner->setCreated(new \DateTime());
        $sotwWinner->setVotes($winner->getVotes());
        $sotwWinner->setYoutube($winner->getYoutubeCode());

        $this->doctrine->persist($sotwWinner);
        $this->doctrine->flush();

        // Announce the winner and unlock the channel
        $io->writeln((string)$winner);
        $this->sotw->announceWinner($winner);
        $this->sotw->addMedals($nominations);
        $this->sotw->openNominations();
        $io->success('Opened nominations');

        // Output post for the forum
        $formatter = new BBCodeFormatter($nominations);
        $bbcode = '```'.$formatter->createMessage().'```';
        $message->channel->send($bbcode);
        $io->success('Showed forum post');
    }
}
