<?php

namespace App\Channel;

use App\Exception\CharacterNotFoundException;
use App\Message\CotsNomination;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Utils\Collection;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
use Jikan\Request\Character\CharacterRequest;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * Class SongOfTheWeek
 *
 * @package App\Discord
 */
class CotsChannel extends Channel
{
    /**
     * @var MalClient
     */
    private $mal;

    /**
     * @var TextChannelInterface
     */
    private $channel;

    /**
     * CotsChannel constructor.
     *
     * @param MalClient            $mal
     * @param TextChannelInterface $channel
     */
    public function __construct(
        MalClient $mal,
        TextChannelInterface $channel
    ) {
        $this->mal = $mal;
        $this->channel = $channel;
    }

    /**
     * @deprecated
     */
    public function closeNominations()
    {
        $this->deny($this->roleId, Channel::ROLE_SEND_MESSAGES);
        $this->message('Er kan nu enkel nog gestemd worden op de nominaties :checkered_flag:');
    }

    /**
     * @param string $season
     *
     * @deprecated
     */
    public function openChannel(string $season)
    {
        $this->allow($this->roleId, Channel::ROLE_SEND_MESSAGES);
        $this->message(sprintf('Bij deze zijn de nominaties voor season  %s geopend!', $season));
    }

    /**
     * @param CotsNomination $nomination
     * @param string         $season
     *
     * @deprecated
     */
    public function announceWinner(CotsNomination $nomination, string $season)
    {
        $this->deny($this->roleId, Channel::ROLE_SEND_MESSAGES);
        $this->message(
            sprintf(
                ":trophy: Het character van %s is **%s**! van **%s**\n"
                ."Genomineerd door %s\nhttps://myanimelist.net/character/%s",
                $season,
                $nomination->getCharacter()->name,
                $nomination->getAnime()->getTitle(),
                $nomination->getAuthor(),
                $nomination->getCharacter()->mal_id
            )
        );
        $this->getLastNominations();
    }

    /**
     * @param int $limit
     *
     * @return Promise
     */
    public function getLastNominations(int $limit = 50): Promise
    {
        $deferred = new Deferred();
        $this->channel
            ->fetchMessages(['limit' => $limit])
            ->done(
                function (Collection $collection) use ($deferred) {
                    $nominations = [];
                    // Filter the messages
                    /** @var Message $message */
                    foreach ($collection->all() as $message) {
                        if (strpos(
                            $message->content,
                            'Bij deze zijn de nominaties voor'
                        ) !== false) {
                            break;
                        }
                        if (!CotsNomination::isNomination($message->content)) {
                            continue;
                        }
                        $nominations[] = $this->loadNomination($message);
                    }
                    // Sort by votes
                    usort(
                        $nominations,
                        function (CotsNomination $a, CotsNomination $b) {
                            if ($a->getVotes() === $b->getVotes()) {
                                return 0;
                            }

                            return $a->getVotes() > $b->getVotes() ? -1 : 1;
                        }
                    );
                    // Return the result
                    $deferred->resolve($nominations);
                }
            );

        return $deferred->promise();
    }

    /**
     * @param Message $message
     *
     * @return CotsNomination
     * @throws CharacterNotFoundException
     */
    public function loadNomination(Message $message): CotsNomination
    {
        if (!CotsNomination::isNomination($message->content)) {
            throw new CharacterNotFoundException('Invalid message '.$message->content);
        }

        try {
            $anime = $this->mal->getAnime(new AnimeRequest(CotsNomination::getAnimeId($message->content)));
            $character = $this->mal->getCharacter(
                new CharacterRequest(CotsNomination::getCharacterId($message->content))
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to load nomination: '.$message->content.' '.$e);
        }

        return CotsNomination::fromMessage($message, $character, $anime);
    }
}
