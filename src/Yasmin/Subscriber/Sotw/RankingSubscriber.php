<?php

namespace App\Yasmin\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Yasmin\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 * @package App\Yasmin\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc sotw ranking';

    /**
     * @var SotwChannel
     */
    private $sotw;

    /**
     * RankingSubscriber constructor.
     * @param SotwChannel $sotw
     */
    public function __construct(SotwChannel $sotw)
    {
        $this->sotw = $sotw;
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
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $nominations = $this->sotw->getLastNominations();
        if (count($nominations) !== 10) {
            $message->reply('Er zijn nog geen 10 nominaties!');

            return;
        }
        $output = ['De huidige song of the week ranking is'];
        foreach ($nominations as $i => $nomination) {
            $output[] = sprintf(
                ':musical_note: %s) %s - %s (**%s**) (**%s** votes) door **%s**',
                $i + 1,
                $nomination->getArtist(),
                $nomination->getTitle(),
                $nomination->getAnime(),
                $nomination->getVotes(),
                $nomination->getAuthor()
            );
        }
        $message->channel->send(implode(PHP_EOL, $output));
    }
}
