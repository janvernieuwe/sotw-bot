<?php

namespace App\Error;

use App\Message\CotsNomination;

/**
 * Class CotsErrorDm
 *
 * @package App\Error
 */
class CotsErrorDm extends AbstractErrorDm
{
    private $message = <<<EOF
Hoi %s,

Je nominatie voor %s:
```
%s
```
is ongeldig omdat:

%s

Je nominatie is hierdoor verwijderd, maar we zien graag een nieuwe (geldige) nominatie van je!
EOF;

    /**
     * @param CotsNomination $nomination
     * @param string         $season
     */
    public function send(CotsNomination $nomination, string $season): void
    {
        // No errors
        if ($this->isValid($nomination)) {
            return;
        }
        $errors = '* '.implode(PHP_EOL.'* ', $this->getErrorArray($nomination));
        $message = sprintf(
            $this->message,
            $nomination->getAuthor(),
            $season,
            $nomination->getContent(),
            $errors
        );
        $this->sendDM($nomination, $message);
    }
}
