<?php

namespace App\Error;

use App\Message\Message;
use RestCord\DiscordClient;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractErrorDm
{
    /**
     * @var DiscordClient
     */
    protected $discord;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * RewatchErrorDm constructor.
     * @param DiscordClient $discord
     * @param ValidatorInterface $validator
     */
    public function __construct(DiscordClient $discord, ValidatorInterface $validator)
    {
        $this->discord = $discord;
        $this->validator = $validator;
    }

    /**
     * @param ConstraintViolationListInterface $errorList
     *
     * @return array
     */
    protected function parseErrors(ConstraintViolationListInterface $errorList): array
    {
        $errors = [];
        /** @var ConstraintViolationInterface $error */
        foreach ($errorList as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }

    /**
     * @param Message $message
     * @param string $content
     */
    protected function sendDM(Message $message, string $content): void
    {
        try {
            $channel = $this->discord->user->createDm(
                [
                    'recipient_id' => $message->getAuthorId(),
                ]
            );
            $this->discord->channel->createMessage(
                [
                    'channel.id' => $channel->id,
                    'content'    => $content,
                ]
            );
        } catch (\Exception $e) {
            echo "Failed to error message {$message->getAuthor()} {$e->getMessage()}".PHP_EOL;
        }
        sleep(1);
    }
}
