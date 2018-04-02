<?php

namespace App\Yasmin\Event;

use CharlotteDunois\Yasmin\Models\MessageReaction;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;

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
     * @var SymfonyStyle
     */
    protected $io;
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
        $this->reaction = $reaction;
        $this->io = $io;
        $this->adminRole = $adminRole;
    }

    /**
     * @return SymfonyStyle
     */
    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

    /**
     * @param SymfonyStyle $io
     */
    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * @return bool
     */
    public function hasIo(): bool
    {
        return $this->io !== null;
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
}
