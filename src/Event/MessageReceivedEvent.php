<?php

namespace App\Event;

use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MessageReceivedEvent
 *
 * @package App\Event
 */
class MessageReceivedEvent extends Event
{
    const NAME = 'yasmin.message_received';

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var int
     */
    private $adminRole;

    /**
     * @var int
     */
    private $permissionsRole;

    /**
     * MessageReceivedEvent constructor.
     *
     * @param Message           $message
     * @param SymfonyStyle|null $io
     * @param int               $adminRole
     * @param int               $permissionsRole
     */
    public function __construct(Message $message, SymfonyStyle $io, int $adminRole, int $permissionsRole)
    {
        parent::__construct($io);
        $this->message = clone $message;
        $this->adminRole = $adminRole;
        $this->permissionsRole = $permissionsRole;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->message->member->roles->has($this->adminRole);
    }

    /**
     * @return int
     */
    public function getPermissionsRole(): int
    {
        return $this->permissionsRole;
    }
}
