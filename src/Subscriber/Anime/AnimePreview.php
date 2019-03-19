<?php

namespace App\Subscriber\Anime;

use App\Event\MessageReceivedEvent;
use Jikan\JikanPHP\JikanPHPClient;
use JikanPHP\Request\Anime\AnimeRequest;
use JikanPHP\Request\Search\AnimeSearchRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnimePreview implements EventSubscriberInterface
{
    private const COMMAND = '!haamc trailer ';

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

        if (null === $anime->getTrailerUrl()) {
            $message->channel->send(':x: No trailer available');

            return;
        }

        $preview = preg_replace('#https://www.youtube.com/embed/(.*)\?.*#', '$1', $anime->getTrailerUrl());
        $message->channel->send(
            sprintf(":movie_camera: **%s** trailer\nhttps://www.youtube.com/watch?v=%s", $anime->getTitle(), $preview)
        );

        echo sprintf('displayed trailer for %s', $name).PHP_EOL;
    }
}
