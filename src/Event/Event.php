<?php

namespace App\Event;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class Event
 *
 * @package App\Event
 */
class Event extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var string
     */
    private $logMessage;

    /**
     * @var bool
     */
    private $logged = false;

    /**
     * Event constructor.
     *
     * @param SymfonyStyle $io
     */
    public function __construct(SymfonyStyle $io)
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
     * @param string $message
     */
    public function setLogMessage(string $message)
    {
        $this->logMessage = $message;
    }

    /**
     * @return SymfonyStyle
     */
    public function getIo(): SymfonyStyle
    {
        $this->logMessage();

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
     * Log the message to the console when fetching IO
     */
    private function logMessage()
    {
        if ($this->logged) {
            return;
        }
        if (!$this->io->isVerbose()) {
            $this->io->writeln($this->logMessage);
        }
        $this->logged = true;
    }
}
