<?php

namespace App\Command\Rewatch;

use App\Message\RewatchNomination;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait DisplayRewatchNomineesTrait
 * @package App\Command
 */
trait DisplayRewatchNomineesTrait
{
    /**
     * @param SymfonyStyle $io
     * @param RewatchNomination[] $messages
     */
    private function displayNominees(SymfonyStyle $io, array $messages): void
    {
        $headers = ['id', 'author', 'anime', 'votes', 'episodes', 'ended on', 'score', 'mal'];
        $data = [];
        foreach ($messages as $id => $message) {
            $data[] = [
                $id + 1,
                $message->getAuthor(),
                $message->getAnime()->title,
                $message->getVotes(),
                $message->getEpisodeCount(),
                $message->getEndDate()->format('Y-m-d'),
                $message->getAnime()->score,
                $message->getContent(),
            ];
        }
        $io->table($headers, $data);
    }
}
