<?php

namespace App\Channel;

use App\Message\RewatchNomination;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Utils\Collection;
use Jikan\Jikan;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeRequest;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * Class SongOfTheWeek
 *
 * @package App\Discord
 */
class RewatchChannel
{
    /**
     * @var TextChannelInterface
     */
    private $channel;

    /**
     * @var Jikan
     */
    private $jikan;

    /**
     * RewatchChannel constructor.
     *
     * @param TextChannelInterface $channel
     * @param MalClient            $jikan
     */
    public function __construct(TextChannelInterface $channel, MalClient $jikan)
    {
        $this->channel = $channel;
        $this->jikan = $jikan;
    }

    /**
     * @return Promise
     */
    public function getNominations(): Promise
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
                        if (!RewatchNomination::isContender($message->content)) {
                            continue;
                        }
                        $nominations[] = $nomination = RewatchNomination::fromMessage($message);
                        $nomination->setAnime($this->jikan->getAnime(new AnimeRequest($nomination->getAnimeId())));
                    }
                    // Sort by votes
                    usort(
                        $nominations,
                        function (RewatchNomination $a, RewatchNomination $b) {
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

//    /**
//     * @return RewatchNomination[]
//     * @throws \Exception
//     */
//    public function getValidNominations(): array
//    {
//        $nominations = $this->getLastNominations();
//        $nominations = array_filter(
//            $nominations,
//            function (RewatchNomination $nomination) {
//                return count($this->validator->validate($nomination)) === 0;
//            }
//        );
//
//        return $nominations;
//    }

//    /**
//     * @param int $limit
//     *
//     * @return RewatchNomination[]
//     * @throws \Exception
//     */
//    public function getLastNominations(int $limit = 10): array
//    {
//        $messages = $this->getMessages($limit + 10);
//        $contenders = [];
//        foreach ($messages as $message) {
//            if (preg_match('/Deze rewatch kijken we naar/', $message['content'])) {
//                break;
//            }
//            if (RewatchNomination::isContender($message['content'])) {
//                $nomination = new RewatchNomination($message);
//                $nomination->setAnime($this->mal->loadAnime($nomination->getAnimeId()));
//                $contenders[] = $nomination;
//            }
//        }
//        $contenders = \array_slice($contenders, 0, $limit);
//
//        return $this->sortByVotes($contenders);
//    }
}
