<?php

namespace App\Yasmin\Subscriber;

use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IsaiSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [MessageReceivedEvent::NAME => 'onHaha'];
    }

    public function onHaha(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if ($message->content !== 'Haha :p') {
            return;
        }

        $event->stopPropagation();
        $message->reply('Wat is er zo grappig?');
        if ($event->hasIo()) {
            $event->getIo()->writeln('Haha response dispatched');
        }
    }
}
