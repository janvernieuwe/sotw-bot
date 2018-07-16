<?php

namespace App\Subscriber\Sotw;

use App\Channel\Channel;
use App\Channel\SongOfTheWeekChannel;
use App\Entity\SotwWinner;
use App\Event\MessageReceivedEvent;
use App\Exception\RuntimeException;
use App\Formatter\BBCodeFormatter;
use App\Message\SotwNomination;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class NextSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc sotw next';

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var int
     */
    private $sotwChannelId;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var GuildChannelInterface|TextChannel
     */
    private $sotwChannel;

    /**
     * @var int
     */
    private $everyoneRole;

    /**
     * @var
     */
    private $channel;

    /**
     * ValidateSubscriber constructor.
     *
     * @param EntityManagerInterface $doctrine
     * @param int                    $sotwChannelId
     * @param int                    $everyoneRole
     */
    public function __construct(EntityManagerInterface $doctrine, int $sotwChannelId, int $everyoneRole)
    {
        $this->doctrine = $doctrine;
        $this->sotwChannelId = $sotwChannelId;
        $this->everyoneRole = $everyoneRole;
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
        $this->message = $message = $event->getMessage();
        if ($message->content !== self::COMMAND || !$event->isAdmin()) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $this->io = $event->getIo();

        $channel = new SongOfTheWeekChannel($this->sotwChannel = $message->client->channels->get($this->sotwChannelId));
        $channel->getNominations(
            function (array $nominations) {
                $this->onNominationsLoaded($nominations);
            }
        );
    }

    /**
     * @param SotwNomination[] $nominations
     *
     * @throws RuntimeException
     */
    private function onNominationsLoaded(array $nominations): void
    {
        if (!\count($nominations)) {
            throw new RuntimeException('No nominations found');
        }
        if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
            throw new RuntimeException('There is no clear winner!');
        }
        $winner = $nominations[0];

        // Add the winner to the database
        $sotwWinner = new SotwWinner();
        $sotwWinner->setMemberId($winner->getAuthorId());
        $sotwWinner->setAnime($winner->getAnime());
        $sotwWinner->setArtist($winner->getArtist());
        $sotwWinner->setTitle($winner->getTitle());
        $sotwWinner->setDisplayName($winner->getAuthor());
        $sotwWinner->setCreated(new \DateTime());
        $sotwWinner->setVotes($winner->getVotes());
        $sotwWinner->setYoutube($winner->getYoutubeCode());

        $this->doctrine->persist($sotwWinner);
        $this->doctrine->flush();

        // Announce the winner and unlock the channel
        $this->io->writeln((string)$winner);
        //$this->sotw->addMedals($nominations); // no medals for now
        $this->sotwChannel->overwritePermissions(
            $this->everyoneRole,
            Channel::ROLE_SEND_MESSAGES,
            0,
            'Song of the week nominations opened'
        );
        $this->sotwChannel->send(
            sprintf(
                ":trophy: De winnaar van week %s is: %s - %s (%s) door <@!%s>\n",
                (int)date('W'),
                $winner->getArtist(),
                $winner->getTitle(),
                $winner->getAnime(),
                $winner->getAuthorId()
            )
        );
        $message = <<<MESSAGE
:musical_note: :musical_note: Bij deze zijn de nominaties voor week %s geopend! :musical_note: :musical_note:

Nomineer volgens onderstaande template (kopieer en plak deze, en zet er dan de gegevens in):
```
artist: 
title: 
anime:  
url: 
```
MESSAGE;
        $this->sotwChannel->send(sprintf($message, date('W') + 1));
        $this->io->success('Opened nominations');

        // Output post for the forum
        $formatter = new BBCodeFormatter($nominations);
        $bbcode = '```'.$formatter->createMessage().'```';
        $this->message->channel->send($bbcode);
        $this->io->success('Showed forum post');
    }
}