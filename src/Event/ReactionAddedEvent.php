<?php

namespace App\Event;

use CharlotteDunois\Yasmin\Models\MessageReaction;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MessageReceivedEvent
 *
 * @package App\Event
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
     * @var int
     */
    private $modRole;

    /**
     * MessageReceivedEvent constructor.
     *
     * @param MessageReaction $reaction
     * @param SymfonyStyle|null $io
     * @param int $adminRole
     * @param int $modRole
     */
    public function __construct(MessageReaction $reaction, SymfonyStyle $io, int $adminRole, int $modRole)
    {
        parent::__construct($io);
        $this->reaction = clone $reaction;
        $this->adminRole = $adminRole;
        $this->modRole = $modRole;
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
     * @return bool
     */
    public function isMod(): bool
    {
        $user = $this->reaction->users->last();
        $member = $this->reaction->message->guild->members->get($user->id);

        return $member->roles->has($this->adminRole) || $member->roles->has($this->modRole);
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
