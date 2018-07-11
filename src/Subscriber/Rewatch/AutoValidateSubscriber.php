<?php

namespace App\Subscriber\Rewatch;

use App\Channel\Channel;
use App\Channel\RewatchChannel;
use App\Entity\RewatchWinner;
use App\Error\RewatchErrorDm;
use App\Event\MessageReceivedEvent;
use App\Exception\RuntimeException;
use App\Message\RewatchNomination;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class AutoValidateSubscriber implements EventSubscriberInterface
{
    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * @var RewatchErrorDm
     */
    private $error;

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * AutoValidateSubscriber constructor.
     *
     * @param RewatchChannel         $rewatch
     * @param RewatchErrorDm         $error
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(
        RewatchChannel $rewatch,
        RewatchErrorDm $error,
        EntityManagerInterface $doctrine
    ) {
        $this->rewatch = $rewatch;
        $this->error = $error;
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
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->rewatch->getChannelId()) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();

        try {
            if (!RewatchNomination::isContender($message->content)) {
                throw new RuntimeException('Not a contender');
            }
            $nomination = RewatchNomination::fromYasmin($message);
            try {
                $anime = $this->rewatch->getMal()->loadAnime($nomination->getAnimeId());
            } catch (\Exception $e) {
                throw new RuntimeException('Invalid anime link');
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            $message->delete();

            return;
        }

        // Check for single nomination for user and anime
        $nominations = $this->rewatch->getValidNominations();
        foreach ($nominations as $check) {
            if ((int)$message->id === $check->getMessageId()) {
                continue;
            }
            if ($nomination->getAnimeId() === $check->getAnimeId()) {
                $nomination->setUniqueAnime(false);
            }
            if ($nomination->getAuthorId() === $check->getAuthorId()) {
                $nomination->setUniqueUser(false);
            }
        }
        // Check if the anime won before
        $previous = $this->doctrine
            ->getRepository(RewatchWinner::class)
            ->findOneBy(['animeId' => $anime->mal_id]);
        $nomination->setPrevious($previous);
        // Enrich with anime data
        $nomination->setAnime($anime);
        $errors = $this->rewatch->validate($nomination);
        // Invalid
        if (count($errors)) {
            /** @noinspection PhpToStringImplementationInspection */
            $io->error($nomination->getAuthor().': '.$nomination->getAnime()->title.PHP_EOL.$errors);
            $this->error->send($nomination);
            $message->delete();

            return;
        }
        // Valid, add reaction
        $message->react('🔼');
        $io->success($nomination->getAnime()->title);
        $nominationCount = count($this->rewatch->getValidNominations());
        if ($nominationCount !== 10) {
            $io->writeln(sprintf('Not starting yet %s/10 nominations', $nominationCount));

            return;
        }
        // Enough nominees, start it
        $message->channel->overwritePermissions(
            $event->getPermissionsRole(),
            Channel::ROLE_VIEW_MESSAGES,
            Channel::ROLE_SEND_MESSAGES,
            'Closed nominations'
        );
        $message->channel->send('Laat het stemmen beginnen :checkered_flag: Enkel stemmen als je mee wil kijken!');
        $message->channel->send('We maken de winnaar zondag namiddag bekend.');
        $io->success('Closed nominations');
    }
}
