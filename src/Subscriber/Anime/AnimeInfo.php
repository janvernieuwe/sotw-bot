<?php

namespace App\Subscriber\Anime;

use App\Event\MessageReceivedEvent;
use Jikan\JikanPHP\JikanPHPClient;
use JikanPHP\Request\Search\AnimeSearchRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnimeInfo implements EventSubscriberInterface
{
    const COMMAND = '!haamc anime ';

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
        $searchRequest = new AnimeSearchRequest($name);
        $searchResult = $this->jikanphp->getAnimeSearch($searchRequest);

        $anime = $searchResult->getResults()[0] ?? null;

        if ($anime === null) {
            $message->reply(sprintf('No anime found for %s', $name));
        }

        $embed = [
            'embed' => [
                'author'    => [
                    'name'     => $anime->getTitle(),
                    'icon_url' => $anime->getImageUrl(),
                    'url'      => $anime->getUrl(),
                ],
                'url'       => $anime->getUrl(),
                'thumbnail' => ['url' => $anime->getImageUrl()],
                //'title' => $anime->getTitle(),
                'fields'    => [
                    [
                        'name'   => 'Format',
                        'value'  => $anime->getType(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Episodes',
                        'value'  => $anime->getEpisodes(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Airing',
                        'value'  => $anime->isAiring() ? 'yes' : 'no',
                        'inline' => true,
                    ],
                    [
                        'name'   => 'score',
                        'value'  => $anime->getScore(),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'Description',
                        'value'  => $anime->getSynopsis(),
                        'inline' => false,
                    ],
                ],
            ],
        ];

        $message->reply($anime->getTitle(), $embed);

        echo sprintf('displayed info for %s', $name).PHP_EOL;
    }
}
