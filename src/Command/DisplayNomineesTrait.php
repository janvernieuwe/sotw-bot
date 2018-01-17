<?php

namespace App\Command;

use App\Entity\SotwContender;
use Symfony\Component\Console\Style\SymfonyStyle;

trait DisplayNomineesTrait
{
    /**
     * @param SymfonyStyle $io
     * @param SotwContender[] $nominees
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
