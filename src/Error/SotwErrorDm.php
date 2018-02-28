<?php

namespace App\Error;

use App\Message\SotwNomination;

/**
 * Class SotwErrorDm
 * @package App\Error
 */
class SotwErrorDm extends AbstractErrorDm
{
    private $message = <<<EOF
Hoi %s,

Helaas is je nominatie voor de song of the week niet geldig omdat:

%s

Je nominatie is hierdoor verwijderd of geflagged, maar we zien graag een nieuwe (geldige) nominatie van je!
EOF;

    /**
     * @param SotwNomination $nomination
     */
    public function send(SotwNomination $nomination): void
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
            $errors
        );
        $this->sendDM($nomination, $message);
    }
}
