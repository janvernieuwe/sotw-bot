<?php

namespace App\Yasmin\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Formatter\BBCodeFormatter;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class ForumSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc sotw forum';

    /**
     * @var SotwChannel
     */
    private $sotw;
    /**
     * @var int
     */
    private $adminRole;

    /**
     * ForumSubscriber constructor.
     * @param int $adminRole
     * @param SotwChannel $sotw
     */
    public function __construct(int $adminRole, SotwChannel $sotw)
    {
        $this->sotw = $sotw;
        $this->adminRole = $adminRole;
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
        if (!$message->member->roles->has($this->adminRole)) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $nominations = $this->sotw->getLastNominations();
        $formatter = new BBCodeFormatter($nominations);
        $bbcode = '```'.$formatter->createMessage().'```';
        $message->channel->send($bbcode);
    }
}
