<?php

namespace App\Command;

use App\Message\SotwNomination;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait DisplayNomineesTrait
 * @package App\Command
 */
trait DisplayNomineesTrait
{
    /**
     * @param SymfonyStyle $io
     * @param SotwNomination[] $nominees
     */
    public function displayNominees(SymfonyStyle $io, array $nominees): void
    {
        $headers = [
            'submitter',
            'artist',
            'title',
            'anime',
            'votes',
        ];
        $data = [];
        foreach ($nominees as $nominee) {
            $data[] = [
                $nominee->getAuthor(),
                $nominee->getArtist(),
                $nominee->getTitle(),
                $nominee->getAnime(),
                $nominee->getVotes(),
            ];
        }
        $io->table($headers, $data);
    }
}
