<?php

namespace App\Channel;

use App\Message\RewatchNomination;
use App\Util\Util;
use Jikan\Jikan;
use Jikan\Model\Anime;
use RestCord\DiscordClient;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeek
 * @package App\Discord
 */
class RewatchChannel extends Channel
{
    /**
     * @var string
     */
    private $role;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Jikan
     */
    private $jikan;
    /**
     * @var array
     */
    private $settings;
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
     * @param array $settings
     */
    public function __construct(
        DiscordClient $discord,
        AdapterInterface $cache,
        Jikan $jikan,
        ValidatorInterface $validator,
        string $channelId,
        array $settings
    ) {
        $this->validator = $validator;
        $this->jikan = $jikan;
        $this->settings = $settings;
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
                return count($this->validate($nomination)) === 0;
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
                $nomination = RewatchNomination::fromMessage($message);
                $key = 'jikan_anime_'.$nomination->getAnimeId();
                if (!$this->cache->hasItem($key)) {
                    $anime = Util::instantiate(Anime::class, $this->jikan->Anime($nomination->getAnimeId())->response);
                    $item = $this->cache->getItem($key);
                    $item->set($anime);
                    $item->expiresAfter(strtotime('+7 day'));
                    $this->cache->save($item);
                }
                $nomination->setAnime($this->cache->getItem($key)->get());
                $contenders[] = $nomination;
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);

        return $this->sortByVotes($contenders);
    }

    /**
     * @param RewatchNomination $nomination
     * @return ConstraintViolationListInterface
     */
    public function validate(RewatchNomination $nomination): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($nomination);
        // Check episode count
        if ($nomination->getEpisodeCount() > $this->settings['max_episodes']) {
            $errors->add(
                new ConstraintViolation(
                    'Too many episodes',
                    null,
                    [],
                    $nomination,
                    '',
                    $nomination->getEpisodeCount(),
                    0,
                    $nomination->getEpisodeCount()
                )
            );
        }
        // Check age
        $max = new \DateTime('-2 years');
        if ($nomination->getEndDate() > $max) {
            $errors->add(
                new ConstraintViolation(
                    'Anime is too new',
                    null,
                    [],
                    $nomination,
                    '',
                    $nomination->getEndDate()->format('Y-m-d'),
                    0,
                    $nomination->getEndDate()->format('Y-m-d')
                )
            );
        }

        return $errors;
    }
}
