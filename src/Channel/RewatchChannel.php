<?php

namespace App\Channel;

use App\Message\RewatchNomination;
use Jikan\Jikan;
use RestCord\DiscordClient;
use Symfony\Component\Cache\Adapter\AdapterInterface;
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
        parent::__construct($discord, $channelId, $cache, $jikan);
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
                $nomination->setAnime($this->loadAnime($nomination->getAnimeId()));
                $contenders[] = $nomination;
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);

        return $this->sortByVotes($contenders);
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
