<?php

namespace App\Yasmin\Event;

use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;

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
     * MessageReceivedEvent constructor.
     * @param Message $message
     * @param SymfonyStyle|null $io
     */
    public function __construct(Message $message, SymfonyStyle $io = null)
    {
        $this->message = $message;
        $this->io = $io;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param SymfonyStyle $io
     */
    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * @return SymfonyStyle
     */
    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

    /**
     * @return bool
     */
    public function hasIo(): bool
    {
        return $this->io !== null;
    }
}
