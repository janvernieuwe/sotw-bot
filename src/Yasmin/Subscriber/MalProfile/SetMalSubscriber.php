<?php

namespace App\Yasmin\Subscriber\MalProfile;

use App\Entity\MyanimelistAccount;
use App\Yasmin\Event\MessageReceivedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package App\Yasmin\Subscriber
 */
class SetMalSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc setmal';

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
        preg_match('/^!haamc setmal (.*)$/', $message->content, $matches);
        $name = $matches[1];
        $id = $message->author->id;
        $account = $repository->findOneByDiscordId($id);
        if ($account === null) {
            $account = new MyanimelistAccount();
            $this->doctrine->persist($account);
        }
        $account->setDiscordId($id);
        $account->setMalNickname($name);
        $this->doctrine->flush();
        $message->reply(sprintf('Je MAL account is geregistreerd als **%s**', $name));
        $io->success('MAL account registered');
    }
}
