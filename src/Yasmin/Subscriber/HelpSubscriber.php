<?php

namespace App\Yasmin\Subscriber;

use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
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
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $help = <<<HELP
```        
All commands are prefixed with !haamc

channel-create <channel-name> <mal-anime-link>
    This command creates an anime channel link in the channel you type it in.
    Users can join the channel by clicking the reactions below it.
    The channel & role can be removed by admins by adding the :put_litter_in_its_place: reaction to the message

cots ranking            (shows the character of the season ranking)
cots start              (start the next character of the season round)
cots finish             (finish and anounce winner of character of the season)
say <channelid> <msg>   (send a message to a channel, admins only)
sotw next               (start the next round of song of the week, admins only)
sotw ranking            (show the current ranking)
sotw forum              (show the current ranking in BBCode, admins only)
rewatch start           (start the next rewatch round, admins only)
rewatch finish          (finish the rewatch round, admins only)
rewatch ranking         (show the current ranking)
```
HELP;
        $message->channel->send($help);
        $io->success('Displayed help');
    }
}
