<?php

namespace App\Command\Emoji;

use App\Message\EmojiNomination;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait DisplayEmojiNomineesTrait
 * @package App\Command
 */
trait DisplayEmojiNomineesTrait
{
    /**
     * @param SymfonyStyle $io
     * @param EmojiNomination[] $messages
     */
    private function displayNominees(SymfonyStyle $io, array $messages): void
    {
        $headers = ['id', 'name', 'author', 'url', 'on_server', 'votes'];
        $data = [];
        foreach ($messages as $id => $message) {
            $data[] = [
                $id + 1,
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
