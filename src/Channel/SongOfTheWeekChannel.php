<?php

namespace App\Channel;

use App\Message\SotwNomination;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Utils\Collection;

/**
 * Class SongOfTheWeekChannel
 *
 * @package App\Channel
 */
class SongOfTheWeekChannel
{
    /**
     * @var TextChannelInterface
     */
    private $channel;

    /**
     * SongOfTheWeekChannel constructor.
     *
     * @param TextChannelInterface $channel
     */
    public function __construct(TextChannelInterface $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Fetches the last nominations with a callback
     *
     * @param callable $callback
     */
    public function getNominations(callable $callback): void
    {
        $this->channel
            ->fetchMessages(['limit' => 20])
            ->done(
                function (Collection $collection) use ($callback) {
                    $nominations = [];
                    // Filter the messages
                    /** @var Message $message */
                    foreach ($collection->all() as $message) {
                        if (strpos($message->content, 'Nomineer volgens onderstaande template') !== false) {
                            break;
                        }
                        if (!SotwNomination::isContenter($message->content)
                            || stripos('url:', $message->content) === false) {
                            continue;
                        }
                        $nominations[] = SotwNomination::fromMessage($message);
                    }
                    // Sort by votes
                    usort(
                        $nominations,
                        function (SotwNomination $a, SotwNomination $b) {
                            if ($a->getVotes() === $b->getVotes()) {
                                return 0;
                            }

                            return $a->getVotes() > $b->getVotes() ? -1 : 1;
                        }
                    );
                    // Return the result
                    $callback($nominations);
                }
            );
    }
}
