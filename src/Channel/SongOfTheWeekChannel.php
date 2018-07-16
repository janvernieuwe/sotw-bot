<?php

namespace App\Channel;

use App\Message\SotwNomination;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Utils\Collection;

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
        $this->channel->fetchMessages(['limit' => 20])
            ->done(
                function (Collection $collection) use ($callback) {
                    $nominations = [];
                    /** @var Message $message */
                    foreach ($collection->all() as $message) {
                        if (strpos($message->content, 'Nomineer volgens onderstaande template') !== false) {
                            break;
                        }
                        if (!SotwNomination::isContenter($message->content)) {
                            continue;
                        }
                        $nominations[] = SotwNomination::fromMessage($message);
                    }
                    $callback($nominations);
                }
            );
    }
}
