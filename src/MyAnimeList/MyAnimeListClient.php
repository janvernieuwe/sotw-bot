<?php

namespace App\MyAnimeList;

use App\Util\Util;
use Jikan\Jikan;
use Jikan\Model\Anime;
use Jikan\Model\Character;
use Jikan\Model\Manga;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Cached MyAnimeList client
 * Class Client
 *
 * @package App\MyAnimeList
 * @deprecated
 */
class MyAnimeListClient
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var Jikan
     */
    private $jikan;


    /**
     * @param string $url
     *
     * @return null|int
     */
    public static function getAnimeId(string $url): ?int
    {
        if (!preg_match('#https?://(www\.)?myanimelist.net/anime/(\d+)#', $url, $anime)) {
            return null;
        }

        return (int)$anime[2];
    }

    /**
     * @param string $url
     *
     * @return null|int
     */
    public static function getIdFromUrl(string $url): ?int
    {
        if (!preg_match('#https?://(www\.)?myanimelist.net/\w+/(\d+)#', $url, $ids)) {
            return null;
        }

        return (int)$ids[2];
    }

    /**
     * @param int $id
     *
     * @return Anime
     */
    public function loadAnime(int $id): Anime
    {
        $key = 'jikan_anime_'.$id;
        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key)->get();
        }

        /** @var Anime $character */
        $anime = Util::instantiate(Anime::class, $this->jikan->Anime($id)->response);
        $item = $this->cache->getItem($key);
        $item->set($anime);
        $item->expiresAfter(strtotime('+7 day'));
        $this->cache->save($item);

        return $anime;
    }

    /**
     * @param int $id
     *
     * @return Manga
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function loadManga(int $id): Manga
    {
        $key = 'jikan_manga_'.$id;
        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key)->get();
        }

        $manga = Util::instantiate(Manga::class, $this->jikan->Manga($id)->response);
        $item = $this->cache->getItem($key);
        $item->set($manga);
        $item->expiresAfter(strtotime('+7 day'));
        $this->cache->save($item);

        return $manga;
    }

    /**
     * @param int $id
     *
     * @return Character
     */
    public function loadCharacter(int $id): Character
    {
        $key = 'jikan_character_'.$id;
        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key)->get();
        }

        /** @var Character $character */
        $character = Util::instantiate(Character::class, $this->jikan->Character($id)->response);
        $item = $this->cache->getItem($key);
        $item->set($character);
        $item->expiresAfter(strtotime('+7 day'));
        $this->cache->save($item);

        return $character;
    }
}
