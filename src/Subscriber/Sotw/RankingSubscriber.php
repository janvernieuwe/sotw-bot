<?php

namespace App\Subscriber\Sotw;

use App\Channel\SongOfTheWeekChannel;
use App\Event\MessageReceivedEvent;
use App\Message\SotwNomination;
use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc sotw ranking';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var int
     */
    private $sotwChannelId;

    /**
     * RankingSubscriber constructor.
     *
     * @param int $sotwChannelId
     */
    public function __construct(int $sotwChannelId)
    {
        $this->sotwChannelId = $sotwChannelId;
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
        $this->message = $message = $event->getMessage();
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $event->getIo()->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $this->io = $event->getIo();

        $sotw = new SongOfTheWeekChannel($message->client->channels->get($this->sotwChannelId));
        $sotw->getNominations(\Closure::fromCallable([$this, 'outputRanking']));
    }

    /**
     * @param SotwNomination[] $nominations
     */
    private function outputRanking(array $nominations): void
    {
        if (!\count($nominations)) {
            $this->message->reply('Er zijn nog geen nominaties!');
            $this->io->writeln('No nominations yet');

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
        $this->message->channel->send(implode(PHP_EOL, $output));
        $this->io->success('Ranking displayed');
    }
}
