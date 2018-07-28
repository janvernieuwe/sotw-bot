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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function getLastNominations(int $limit = 25): Promise
    {
        $deferred = new Deferred();
        $this->channel
            ->fetchMessages(['limit' => 20])
            ->done(
                function (Collection $collection) use ($deferred) {
                    $nominations = [];
                    // Filter the messages
                    /** @var Message $message */
                    foreach ($collection->all() as $message) {
                        if (strpos(
                            $message->content,
                            'Bij deze zijn de nominaties voor de rewatch geopend!'
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

        $anime = $this->mal->getAnime(new AnimeRequest(CotsNomination::getAnimeId($message->content)));
        $character = $this->mal->getCharacter(new CharacterRequest(CotsNomination::getCharacterId($message->content)));

        return CotsNomination::fromMessage($message, $character, $anime);
    }

    /**
     * @return string
     * @deprecated
     */
    public function getTop10(): string
    {
        $output = ['De huidige Character of the season ranking is'];
        foreach ($this->getLastNominations(10) as $i => $nomination) {
            $voiceActors = $nomination->getCharacter()->voice_actor;
            $output[] = sprintf(
                ":mens: %s) **%s**, *%s*\nvotes: **%s** | door: *%s* | voice actor: *%s* | score: %s",
                $i + 1,
                $nomination->getCharacter()->name,
                $nomination->getAnime()->title,
                $nomination->getVotes(),
                $nomination->getAuthor(),
                count($voiceActors) ? $nomination->getCharacter()->voice_actor[0]['name'] : 'n/a',
                $nomination->getAnime()->score
            );
        }

        return implode(PHP_EOL, $output);
    }
}
