<?php

namespace App\Yasmin\Subscriber\MalProfile;

use App\Entity\MyanimelistAccount;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package App\Yasmin\Subscriber
 */
class GetMalSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc getmal';

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * AutoValidateSubscriber constructor.
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(
        EntityManagerInterface $doctrine
    ) {
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
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $io = $event->getIo();
        $repository = $this->doctrine->getRepository(MyanimelistAccount::class);
        if (!$message->mentions->users->count()) {
            $message->reply('🔴 Je moet iemand mentionen om zijn MyAnimeList op te zoeken');

            return;
        }
        /** @var User $user */
        $user = $message->mentions->users->first();
        $account = $repository->findOneByDiscordId((int)$user->id);
        if ($account === null) {
            $message->reply('🔴 Nog geen account gevonden');

            return;
        }
        $message->reply(
            sprintf(
                "🔵 %s\'s MyAnimeList nickname is **%s**\nhttps://myanimelist.net/animelist/%s",
                $user->username,
                $account->getMalNickname(),
                $account->getMalNickname()
            )
        );
        $io->success('MyAnimeList account displayed');
    }
}
