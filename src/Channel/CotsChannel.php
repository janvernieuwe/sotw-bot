<?php

namespace App\Channel;

use App\Exception\CharacterNotFoundException;
use App\Message\CotsNomination;
use CharlotteDunois\Yasmin\Models\Message;
use Jikan\Jikan;
use RestCord\DiscordClient;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeek
 * @package App\Discord
 */
class CotsChannel extends Channel
{
    /**
     * CotsChannel constructor.
     * @param DiscordClient $discord
     * @param AdapterInterface $cache
     * @param Jikan $jikan
     * @param ValidatorInterface $validator
     * @param int $channelId
     */
    public function __construct(
        DiscordClient $discord,
        AdapterInterface $cache,
        Jikan $jikan,
        ValidatorInterface $validator,
        int $channelId
    ) {
        parent::__construct($discord, $channelId, $cache, $jikan);
    }

    /**
     * @param int $limit
     * @return CotsNomination[]
     * @throws \Exception
     */
    public function getLastNominations(int $limit = 10): array
    {
        $messages = $this->getMessages($limit + 10);
        $contenders = [];
        foreach ($messages as $message) {
            if (preg_match('/Bij deze zijn de nominaties voor/', $message['content'])) {
                break;
            }
            if (CotsNomination::isNomination($message['content'])) {
                $anime = $this->loadAnime(CotsNomination::getAnimeId($message['content']));
                $character = $this->loadCharacter(CotsNomination::getCharacterId($message['content']));
                $nomination = new CotsNomination($message, $character, $anime);
                $contenders[] = $nomination;
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);

        return $this->sortByVotes($contenders);
    }


    /**
     * @param int $roleId
     */
    public function start(int $roleId)
    {
        $this->allow($roleId, Channel::ROLE_SEND_MESSAGES);
        $this->message('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');
    }

    /**
     * @param Message $message
     * @return CotsNomination
     * @throws CharacterNotFoundException
     */
    public function loadNomination(Message $message): CotsNomination
    {
        if (!CotsNomination::isNomination($message->content)) {
            throw new CharacterNotFoundException('Invalid message '.$message->content);
        }

        $anime = $this->loadAnime(CotsNomination::getAnimeId($message->content));
        $character = $this->loadCharacter(CotsNomination::getCharacterId($message->content));

        return CotsNomination::fromYasmin($message, $character, $anime);
    }
}
