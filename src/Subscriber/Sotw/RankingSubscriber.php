<?php

namespace App\Subscriber\Sotw;

use App\Channel\SotwChannel;
use App\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
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
     *
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
        $io = $event->getIo();
        $nominations = $this->sotw->getLastNominations();
        $nominationCount = count($nominations);
        if ($nominationCount !== 10) {
            $message->reply('Er zijn nog geen 10 nominaties!');
            $io->writeln(sprintf('Not enough nominations %s/10 nominations', $nominationCount));

            return;
        }
        $output = ['De huidige song of the week ranking is'];
        foreach ($nominations as $i => $nomination) {
            $output[] = sprintf(
                ":radio: %s) **%s** - **%s**\nvotes: **%s** | anime: *%s* | door: %s",
                str_pad($i + 1, 2, ' '),
                $nomination->getArtist(),
                $nomination->getTitle(),
                $nomination->getVotes(),
                $nomination->getAnime(),
                $nomination->getAuthor()
            );
        }
        $message->channel->send(implode(PHP_EOL, $output));
        $io->success('Ranking displayed');
    }
}
