<?php

namespace App\Yasmin\Subscriber;

use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Help command for normal users
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class HelpSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc help';

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
        if ($message->content !== self::COMMAND) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $help = <<<HELP
```
Nani TF doet deze bot allemaal?
        
!haamc cots ranking            (Character of the season ranking)
!haamc sotw ranking            (Song of the week ranking)
!haamc rewatch ranking         (Rewatch nominatie ranking)
!haamc season ranking <page>   (Seasonal anime ranking, per 10)
!bikkelpunt                    (Claim je bikkel punt, enkel in bikkeltijd)
!haamc setmal JouwMalAccount   (Claim je MAL account)
!haamc getmal :mention:        (Indien gementionde user een mal geset heeft toont deze de account)
```
HELP;
        $message->channel->send($help);
        $io->success('Displayed help');
    }
}
