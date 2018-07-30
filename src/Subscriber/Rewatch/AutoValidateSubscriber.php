<?php

namespace App\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Channel\RewatchChannel;
use App\Entity\RewatchWinner;
use App\Error\Messenger;
use App\Event\MessageReceivedEvent;
use App\Exception\RuntimeException;
use App\Message\RewatchNomination;
use Doctrine\ORM\EntityManagerInterface;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class AutoValidateSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var MalClient
     */
    private $mal;

    /**
     * @var MessageReceivedEvent
     */
    private $event;

    /**
     * @var RewatchNomination
     */
    private $nomination;

    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var int
     */
    private $rewatchChannelId;

    /**
     * AutoValidateSubscriber constructor.
     *
     * @param EntityManagerInterface $doctrine
     * @param MalClient              $mal
     * @param ValidatorInterface     $validator
     * @param int                    $rewatchChannelId
     */
    public function __construct(
        EntityManagerInterface $doctrine,
        MalClient $mal,
        ValidatorInterface $validator,
        int $rewatchChannelId
    ) {
        $this->doctrine = $doctrine;
        $this->mal = $mal;
        $this->validator = $validator;
        $this->rewatchChannelId = $rewatchChannelId;
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
        $this->event = $event;
        $message = $event->getMessage();
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->rewatchChannelId) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        try {
            if (!RewatchNomination::isContender($message->content)) {
                throw new RuntimeException('Not a contender');
            }
            $nomination = RewatchNomination::fromMessage($message);
            $anime = $this->mal->getAnime(new AnimeRequest($nomination->getAnimeId()));
            $nomination->setAnime($anime);
            $this->nomination = $nomination;
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->delete();

            return;
        }

        $rewatch = new RewatchChannel($message->channel, $this->mal);
        // Check for single nomination for user and anime
        $rewatch->getNominations()
            ->then(\Closure::fromCallable([$this, 'onMessagesLoaded']));
    }

    /**
     * @param RewatchNomination[] $nominations
     */
    private function onMessagesLoaded(array $nominations): void
    {
        $event = $this->event;
        $message = $event->getMessage();
        $io = $event->getIo();

        foreach ($nominations as $check) {
            if ((int)$message->id === $check->getMessageId()) {
                continue;
            }
            if ($this->nomination->getAnimeId() === $check->getAnimeId()) {
                $this->nomination->setUniqueAnime(false);
            }
            if ($this->nomination->getAuthorId() === $check->getAuthorId()) {
                $this->nomination->setUniqueUser(false);
            }
        }

        // Check if the anime won before
        $previous = $this->doctrine
            ->getRepository(RewatchWinner::class)
            ->findOneBy(['animeId' => $this->nomination->getAnimeId()]);
        $this->nomination->setPrevious($previous);
        // Enrich with anime data
        $errors = $this->validator->validate($this->nomination);
        // Invalid
        if (count($errors)) {
            (new Messenger($message, $errors, $io))->send();

            return;
        }
        // Valid, add reaction
        $message->react('🔼');
        $io->success($this->nomination->getAnime()->getTitle());
        $nominationCount = count($nominations);
        if ($nominationCount !== 10) {
            $io->writeln(sprintf('Not starting yet %s/10 nominations', $nominationCount));

            return;
        }
        // Enough nominees, start it
        Channel::close($message->channel, $event->getPermissionsRole());
        $message->channel->send('Laat het stemmen beginnen :checkered_flag: Enkel stemmen als je mee wil kijken!');
        $message->channel->send('We maken de winnaar zondag namiddag bekend.');
        $io->success('Closed nominations');
    }
}
