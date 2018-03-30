<?php

namespace App\Channel;

use App\Message\RewatchNomination;
use App\Util\Util;
use Jikan\Jikan;
use Jikan\Model\Anime;
use RestCord\DiscordClient;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeek
 * @package App\Discord
 */
class RewatchChannel extends Channel
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Jikan
     */
    private $jikan;

    /**
     * @var FilesystemAdapter
     */
    private $cache;

    /**
     * SongOfTheWeek constructor.
     * @param DiscordClient $discord
     * @param AdapterInterface $cache
     * @param Jikan $jikan
     * @param ValidatorInterface $validator
     * @param string $channelId
     */
    public function __construct(
        DiscordClient $discord,
        AdapterInterface $cache,
        Jikan $jikan,
        ValidatorInterface $validator,
        string $channelId
    ) {
        $this->validator = $validator;
        $this->jikan = $jikan;
        $this->cache = $cache;
        parent::__construct($discord, $channelId);
    }

    /**
     * @return RewatchNomination[]
     * @throws \Exception
     */
    public function getValidNominations(): array
    {
        $nominations = $this->getLastNominations();
        $nominations = array_filter(
            $nominations,
            function (RewatchNomination $nomination) {
                return count($this->validator->validate($nomination)) === 0;
            }
        );

        return $nominations;
    }

    /**
     * @param int $limit
     * @return RewatchNomination[]
     * @throws \Exception
     */
    public function getLastNominations(int $limit = 10): array
    {
        $messages = $this->getMessages($limit + 10);
        $contenders = [];
        foreach ($messages as $message) {
            if (preg_match('/Deze rewatch kijken we naar/', $message['content'])) {
                break;
            }
            if (RewatchNomination::isContender($message['content'])) {
                $nomination = new RewatchNomination($message);
                $this->addAnime($nomination);
                $contenders[] = $nomination;
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);

        return $this->sortByVotes($contenders);
    }

    /**
     * @param RewatchNomination $nomination
     * @return RewatchNomination
     */
    public function addAnime(RewatchNomination $nomination): RewatchNomination
    {
        $key = 'jikan_anime_'.$nomination->getAnimeId();
        if (!$this->cache->hasItem($key)) {
            $anime = Util::instantiate(Anime::class, $this->jikan->Anime($nomination->getAnimeId())->response);
            $item = $this->cache->getItem($key);
            $item->set($anime);
            $item->expiresAfter(strtotime('+7 day'));
            $this->cache->save($item);
        }
        $nomination->setAnime($this->cache->getItem($key)->get());

        return $nomination;
    }

    /**
     * @param int $roleId
     */
    public function startVoting(int $roleId)
    {
        $this->deny($roleId, Channel::ROLE_SEND_MESSAGES);
        $this->message('Laat het stemmen beginnen :checkered_flag: Enkel stemmen als je mee wil kijken!');
        $this->message('We maken de winnaar zondag namiddag bekend.');
    }

    /**
     * @param int $roleId
     */
    public function openNominations(int $roleId)
    {
        $this->allow($roleId, Channel::ROLE_SEND_MESSAGES);
        $this->message('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');
    }
}
