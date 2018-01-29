<?php

namespace App\Formatter;

use App\Message\SotwNomination;

class BBCodeFormatter
{
    /**
     * @var SotwNomination[]
     */
    private $nominations;

    /**
     * BBCodeFormatter constructor.
     * @param SotwNomination[] $nominations
     */
    public function __construct(array $nominations)
    {
        $this->nominations = $nominations;
    }

    /**
     * @return string
     */
    public function createMessage(): string
    {
        $msg = sprintf(
            "\nWeek %s: %s - %s (%s) door %s - %s\n[spoiler]\n",
            date('W'),
            $this->nominations[0]->getArtist(),
            $this->nominations[0]->getTitle(),
            $this->nominations[0]->getAnime(),
            $this->nominations[0]->getAuthor(),
            $this->nominations[0]->getYoutube()
        );
        foreach ($this->nominations as $nomination) {
            $template = <<<TEMPLATE
[spoiler="%s Votes (%s) : %s - %s (%s)"][yt]%s[/yt][/spoiler]

TEMPLATE;

            $msg .= sprintf(
                $template,
                $nomination->getVotes(),
                $nomination->getAuthor(),
                $nomination->getArtist(),
                $nomination->getTitle(),
                $nomination->getAnime(),
                $nomination->getYoutubeCode()
            );
        }
        $msg .= '[/spoiler]'.PHP_EOL;

        return $msg;
    }
}
