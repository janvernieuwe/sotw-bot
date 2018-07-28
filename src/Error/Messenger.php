<?php

namespace App\Error;

use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class Messenger
 *
 * @package App\Error
 */
class Messenger
{
    private const TIMEOUT = 10;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var ConstraintViolationListInterface
     */
    private $errors;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * Messenger constructor.
     *
     * @param Message                          $message
     * @param ConstraintViolationListInterface $errors
     * @param SymfonyStyle                     $io
     */
    public function __construct(Message $message, ConstraintViolationListInterface $errors, SymfonyStyle $io)
    {
        $this->message = $message;
        $this->errors = $errors;
        $this->io = $io;
    }

    public function send(): void
    {
        $this->io->error((string)$this->errors);
        $this->message->reply(
            sprintf(
                "Je nominatie niet geldig om de volgende redenen:\n%s",
                $this->formatErrors($this->errors)
            )
        )->done(
            function (Message $errorMessage) {
                $errorMessage->delete(self::TIMEOUT);
                $this->message->delete(self::TIMEOUT);
                $this->io->success('Removed message');
            }
        );
    }

    /**
     * @param ConstraintViolationListInterface $errorList
     *
     * @return string
     */
    private function formatErrors(ConstraintViolationListInterface $errorList): string
    {
        $errors = [];
        /** @var ConstraintViolationInterface $error */
        foreach ($errorList as $error) {
            $errors[] = sprintf(':x: %s', $error->getMessage());
        }

        return PHP_EOL.implode(PHP_EOL, $errors);
    }
}
