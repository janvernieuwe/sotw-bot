<?php

namespace App\Subscriber\Anime;

use App\Event\MessageReceivedEvent;
use Jikan\JikanPHP\JikanPHPClient;
use JikanPHP\Request\Seasonal\SeasonalRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TopSeasonalExport implements EventSubscriberInterface
{
    private const COMMAND = '!haamc seasonexport';

    /**
     * @var JikanPHPClient
     */
    private $jikanphp;

    /**
     * AnimeInfo constructor.
     *
     * @param JikanPHPClient $jikanphp
     */
    public function __construct(JikanPHPClient $jikanphp)
    {
        $this->jikanphp = $jikanphp;
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
        if ($message->content !== self::COMMAND || !$event->isAdmin()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        if (!$fp = fopen('php://memory', 'wb')) {
            $io->error('Cannot open fp');
        }

        $season = $this->jikanphp->getSeasonal(new SeasonalRequest());
        fputcsv($fp, ['title', 'url', 'channel name']);
        foreach ($season->getAnime() as $anime) {
            $channelname = strtolower($anime->getTitle());
            $channelname = preg_replace('/\W+/', '-', $channelname);

            fputcsv($fp, [$anime->getTitle(), $anime->getUrl(), $channelname]);
        }
        rewind($fp);
        $contents = stream_get_contents($fp);
        $message->reply(
            'Here is your export ',
            ['files' => [['name' => $season->seasonName.'_export.csv', 'data' => $contents]]]
        );


        echo 'Exported top anime'.PHP_EOL;
    }
}
