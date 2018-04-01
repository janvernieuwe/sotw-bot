<?php

namespace App\Yasmin\Event;

use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MessageReceivedEvent
 * @package App\Yasmin\Event
 */
class MessageReceivedEvent extends Event
{
    const NAME = 'yasmin.message_received';

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var SymfonyStyle
     */
    protected $io;
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
     * @param Message $message
     * @param SymfonyStyle|null $io
     * @param int $adminRole
     * @param int $permissionsRole
     */
    public function __construct(Message $message, SymfonyStyle $io, int $adminRole, int $permissionsRole)
    {
        $this->message = $message;
        $this->io = $io;
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
