<?php

namespace App\Command;

use App\Message\EmojiNomination;
use Symfony\Component\Console\Style\SymfonyStyle;

trait DisplayEmojiNomineesTrait
{
    /**
     * @param SymfonyStyle $io
     * @param EmojiNomination[] $messages
     */
    private function displayNominees(SymfonyStyle $io, array $messages): void
    {
        $headers = ['name', 'author', 'url', 'on_server', 'votes'];
        $data = [];
        foreach ($messages as $message) {
            $data[] = [
                $message->getName(),
                $message->getAuthor(),
                $message->getUrl(),
                $message->isGuildNomination() ? 'yes' : 'no',
                $message->getVotes(),
            ];
        }
        $io->table($headers, $data);
    }
}
