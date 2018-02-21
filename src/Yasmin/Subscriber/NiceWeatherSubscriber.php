<?php

namespace App\Yasmin\Subscriber;

use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NiceWeatherSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [MessageReceivedEvent::NAME => 'onNiceWeather'];
    }

    public function onNiceWeather(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if (!preg_match('/lekker weer/i', $message->content)) {
            return;
        }

        $event->stopPropagation();
        $message->reply('Ja! Heerlijk!');
        if ($event->hasIo()) {
            $event->getIo()->writeln('Nice weather dispatched');
        }
    }
}
