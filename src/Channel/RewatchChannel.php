<?php

namespace App\Channel;

use App\Message\RewatchNomination;
use App\Message\SotwNomination;
use App\Util\Util;
use Jikan\Jikan;
use Jikan\Model\Anime;
use RestCord\DiscordClient;
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
     * SongOfTheWeek constructor.
     * @param DiscordClient $discord
     * @param Jikan $jikan
     * @param string $channelId
     * @param ValidatorInterface $validator
     * @param string $role
     */
    public function __construct(
        DiscordClient $discord,
        Jikan $jikan,
        ValidatorInterface $validator,
        string $channelId,
        string $role,
        array $settings
    ) {
        $this->role = $role;
        $this->validator = $validator;
        parent::__construct($discord, $channelId);
        $this->jikan = $jikan;
        $this->settings = $settings;
    }

    /**
     * @param int $limit
     * @return RewatchNomination[]
     */
    public function getLastNominations(int $limit = 10): array
    {
        $messages = $this->getMessages($limit + 10);
        $contenders = [];
        foreach ($messages as $message) {
            if (RewatchNomination::isContender($message['content'])) {
                $nomination = RewatchNomination::fromMessage($message);
                $anime = Util::instantiate(Anime::class, $this->jikan->Anime($nomination->getAnimeId())->response);
                $nomination->setAnime($anime);
                $contenders[] = $nomination;
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);

        return $this->sortByVotes($contenders);
    }

    /**
     * @return RewatchNomination[]
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
