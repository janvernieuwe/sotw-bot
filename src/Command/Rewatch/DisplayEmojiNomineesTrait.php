<?php

namespace App\Command\Rewatch;

use App\Message\RewatchNomination;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait DisplayEmojiNomineesTrait
 * @package App\Command
 */
trait DisplayEmojiNomineesTrait
{
    /**
     * @param SymfonyStyle $io
     * @param RewatchNomination[] $messages
     */
    private function displayNominees(SymfonyStyle $io, array $messages): void
    {
        $headers = ['id', 'author', 'anime', 'votes', 'episodes', 'ended on', 'mal'];
        $data = [];
        foreach ($messages as $id => $message) {
            $data[] = [
                $id + 1,
                $message->getAuthor(),
                $message->getAnime()->title,
                $message->getVotes(),
                $message->getEpisodeCount(),
                $message->getEndDate()->format('Y-m-d'),
                $message->getContent(),
            ];
        }
        $io->table($headers, $data);
    }
}
