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
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $name = str_replace(self::COMMAND, '', $message->content);


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

        $embed = [
            'embed' => [
                'url'       => $anime->getUrl(),
                'thumbnail' => ['url' => $anime->getImageUrl()],
                'title'     => $anime->getTitle(),
                'fields'    => [
                    [
                        'name'   => 'Format',
                        'value'  => $anime->getType(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Episodes',
                        'value'  => $anime->getEpisodes() ?? 'n/a',
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Status',
                        'value'  => $anime->getStatus(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Score',
                        'value'  => (string)($anime->getScore() ?? 'n/a'),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Season',
                        'value'  => $anime->getPremiered(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Genres',
                        'value'  => implode(', ', $anime->getGenres()),
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
