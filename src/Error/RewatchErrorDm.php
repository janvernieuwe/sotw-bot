<?php

namespace App\Error;

use App\Message\RewatchNomination;
use RestCord\DiscordClient;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RewatchErrorDm
 * @package App\Error
 */
class RewatchErrorDm
{
    /**
     * @var DiscordClient
     */
    private $discord;

    private $message = <<<EOF
Hoi %s,

Helaas is je nominatie (%s) voor de rewatch niet geldig omdat:

%s

Hierbij nog eens de regels waar een nominatie aan moet voldoen:

* Nominatie is enkel een link naar de MAL pagina van de anime
* De serie moet mnimaal 10 en maximaal 13 afleveringen bevatten
* Er zijn maximaal 10 nominaties
* Geen hentai :smirk:
* De serie moet minstens 2 jaar oud zijn (eind datum)

Je nominatie is hierdoor verwijderd of geflagged, maar we zien graag een nieuwe (geldige) nominatie van je!
EOF;
    /**
     * @var ValidatorInterface
     */
    private $validator;

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
     * @param RewatchNomination $nomination
     */
    public function send(RewatchNomination $nomination): void
    {
        $errors = $this->validator->validate($nomination);
        // No errors
        if (!count($errors)) {
            return;
        }
        // Already messaged
        if ($nomination->hasReaction('❌')) {
            return;
        }
        $errors = $this->parseErrors($errors);
        $errors = '* '.implode(PHP_EOL.'* ', $errors);
        $message = sprintf(
            $this->message,
            $nomination->getAuthor(),
            $nomination->getAnime()->title,
            $errors
        );
        try {
            $channel = $this->discord->user->createDm(
                [
                    'recipient_id' => $nomination->getAuthorId(),
                ]
            );
            $this->discord->channel->createMessage(
                [
                    'channel.id' => $channel->id,
                    'content'    => $message,
                ]
            );
        } catch (\Exception $e) {
            echo "Failed to error message {$nomination->getAuthor()} {$e->getMessage()}".PHP_EOL;
        }

        sleep(1);
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
}
