<?php

namespace App\Command;

/**
 * Class CommandParser
 *
 * @package App\Command
 */
class CommandParser
{
    /**
     * @var int
     */
    private $categoryId;

    /**
     * @var string
     */
    private $command;

    /**
     * CommandParser constructor.
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        $this->command = $command;
        $this->parse();
    }

    /**
     * @return string
     */
    public function parse(): string
    {
        $cmd = $this->command;
        if (preg_match('/\s?--category=(\d+)/', $cmd, $matches)) {
            $this->categoryId = (int)$matches[1];
            $cmd = str_replace($matches[0], '', $cmd);
        }

        return $cmd;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->parse();
    }

    /**
     * @return int
     */
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $arg
     *
     * @return int|null
     */
    public function parseArgument(string $arg):?int
    {
        if (preg_match('/\s?--'.$arg.'=(\d+)/', $this->command, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
