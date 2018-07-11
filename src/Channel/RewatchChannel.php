<?php

namespace App\Channel;

use App\Message\RewatchNomination;
use App\MyAnimeList\MyAnimeListClient;
use RestCord\DiscordClient;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeek
 *
 * @package App\Discord
 */
class RewatchChannel
{
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var MyAnimeListClient
     */
    private $mal;
    /**
     * @var string
     */
    private $channelId;

    /**
     * SongOfTheWeek constructor.
     *
     * @param MyAnimeListClient  $mal
     * @param ValidatorInterface $validator
     * @param string             $channelId
     */
    public function __construct(
        MyAnimeListClient $mal,
        ValidatorInterface $validator,
        string $channelId
    ) {
        $this->validator = $validator;
        $this->mal = $mal;
        $this->channelId = $channelId;
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
     *
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
                $nomination->setAnime($this->mal->loadAnime($nomination->getAnimeId()));
                $contenders[] = $nomination;
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);

        return $this->sortByVotes($contenders);
    }

    /**
     * @param RewatchNomination $nomination
     *
     * @return ConstraintViolationListInterface
     */
    public function validate(RewatchNomination $nomination): ConstraintViolationListInterface
    {
        return $this->validator->validate($nomination);
    }
}
