<?php

namespace App\Yasmin\Event;

use CharlotteDunois\Yasmin\Models\MessageReaction;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MessageReceivedEvent
 * @package App\Yasmin\Event
 */
class ReactionAddedEvent extends Event
{
    const NAME = 'yasmin.reaction_added';

    /**
     * @var MessageReaction
     */
    protected $reaction;

    /**
     * @var int
     */
    private $adminRole;

    /**
     * MessageReceivedEvent constructor.
     * @param MessageReaction $reaction
     * @param SymfonyStyle|null $io
     * @param int $adminRole
     */
    public function __construct(MessageReaction $reaction, SymfonyStyle $io, int $adminRole)
    {
        parent::__construct($io);
        $this->reaction = $reaction;
        $this->adminRole = $adminRole;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        $user = $this->reaction->users->last();
        $member = $this->reaction->message->guild->members->get($user->id);

        return $member->roles->has($this->adminRole);
    }

    /**
     * @return MessageReaction
     */
    public function getReaction(): MessageReaction
    {
        return $this->reaction;
    }

    /**
     * @return bool
     */
    public function isBotMessage(): bool
    {
        return $this->reaction->message->author->bot;
    }
}
