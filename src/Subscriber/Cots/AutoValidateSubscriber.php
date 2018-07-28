<?php

namespace App\Subscriber\Cots;

use App\Channel\Channel;
use App\Channel\CotsChannel;
use App\Entity\Reaction;
use App\Error\Messenger;
use App\Event\MessageReceivedEvent;
use App\Message\CotsNomination;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use CharlotteDunois\Yasmin\Models\Message;
use Jikan\MyAnimeList\MalClient;
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
    public const LIMIT = 25;

    /**
     * @var string
     */
    private $season;

    /**
     * @var int
     */
    private $cotsChannelId;

    /**
     * @var MalClient
     */
    private $jikan;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var int
     */
    private $everyoneRole;

    /**
     * AutoValidateSubscriber constructor.
     *
     * @param string             $season
     * @param int                $cotsChannelId
     * @param MalClient          $jikan
     * @param ValidatorInterface $validator
     * @param int                $roleId
     */
    public function __construct(
        string $season,
        int $cotsChannelId,
        MalClient $jikan,
        ValidatorInterface $validator,
        int $roleId
    ) {
        $this->season = $season;
        $this->cotsChannelId = $cotsChannelId;
        $this->jikan = $jikan;
        $this->validator = $validator;
        $this->everyoneRole = $roleId;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * 25 max
     * unique
     *
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        /** @noinspection PhpUndefinedFieldInspection */
        if ((int)$message->channel->id !== $this->cotsChannelId) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $cotsChannel = new CotsChannel($this->jikan, $message->client->channels->get($this->cotsChannelId));

        if (!CotsNomination::isNomination($message->content)) {
            $message->reply(':x: Ontbrekende anime en/of character link')
                ->done(
                    function (Message $errorMessage) use ($message, $io) {
                        $message->delete(10);
                        $errorMessage->delete(10);
                        $io->error('Invalid nomination');
                    }
                );

            return;
        }

        $nomination = $cotsChannel->loadNomination($message);
        // Set the season for validation
        $nomination->setSeason($this->season);

        $errors = $this->validator->validate($nomination);
        // Validate the nomination
        if ($errors->count()) {
            (new Messenger($message, $errors, $io))->send();

            return;
        }
        // Success
        $message->react(Reaction::VOTE);
        $io->success($nomination->getCharacter()->name.' - '.$nomination->getAnime()->getTitle());
        // Check total nominations
        $nominations = $cotsChannel->getLastNominations();
        $nominationCount = count($nominations);
        if ($nominationCount !== self::LIMIT) {
            $io->writeln(sprintf('Not locking yet %s/%s nominations', $nominationCount, self::LIMIT));

            return;
        }
        // Close channel when limit is reached
        /** @var GuildChannelInterface $guildChannel */
        $guildChannel = $message->guild->channels->get($this->cotsChannelId);
        $guildChannel->overwritePermissions(
            $this->everyoneRole,
            0,
            Channel::ROLE_SEND_MESSAGES,
            'Closed Cots nominations'
        );
        $io->success('Closed nominations');
    }
}
