<?php

namespace App\Subscriber\Anime;

use App\Event\MessageReceivedEvent;
use Jikan\JikanPHP\JikanPHPClient;
use JikanPHP\Request\Anime\AnimeRequest;
use JikanPHP\Request\Search\AnimeSearchRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnimeInfo implements EventSubscriberInterface
{
    private const COMMAND = '!haamc anime ';
    private const COMMAND2 = '!ha ';

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
        if (strpos($message->content, self::COMMAND) !== 0 && strpos($message->content, self::COMMAND2) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();
        $name = str_replace(array(self::COMMAND2, self::COMMAND), '', $message->content);

        try {
            $searchRequest = new AnimeSearchRequest($name);
            $searchResult = $this->jikanphp->getAnimeSearch($searchRequest);
            $animeSearch = $searchResult->getResults()[0] ?? null;
            if ($animeSearch === null) {
                throw new \Exception(sprintf('No anime found for %s', $name));
            }
            $animeRequest = new AnimeRequest($animeSearch->getMalId());
            $anime = $this->jikanphp->getAnime($animeRequest);
        } catch (\Exception $e) {
            $message->reply(':x: Something wenth wrong, try again later');

            return;
        }

        $genres = implode(', ', $anime->getGenres());
        $embed = [
            'embed' => [
                'url'       => $anime->getUrl(),
                'thumbnail' => ['url' => $anime->getImageUrl()],
                'title'     => $anime->getTitle(),
                'fields'    => [
                    [
                        'name'   => 'Format',
                        'value'  => $anime->getType() ?? 'n/a',
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Episodes',
                        'value'  => (string)($anime->getEpisodes() ?? 'n/a'),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Status',
                        'value'  => $anime->getStatus() ?? 'n/a',
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Score',
                        'value'  => (string)($anime->getScore() ?? 'n/a'),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Season',
                        'value'  => $anime->getPremiered() ?? 'n/a',
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Genres',
                        'value'  => $genres ? $genres : 'n/a',
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Description',
                        'value'  => substr($anime->getSynopsis(), 0, 1000),
                        'inline' => false,
                    ],
                ],
            ],
        ];

        $message->channel->send('', $embed);

        echo sprintf('displayed info for %s', $name).PHP_EOL;
    }
}
