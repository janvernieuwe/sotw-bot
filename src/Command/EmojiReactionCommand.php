<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EmojiReactionCommand extends ContainerAwareCommand
{
    use DisplayEmojiNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:react')
            ->setDescription('Adds voting reactions to nominations')
            ->setHelp('Adds the voting reaction');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $channel = $this->getContainer()->get('discord.channel.emoji');
        $nominations = $channel->getNominations();
        $this->displayNominees($io, $nominations);
        foreach ($nominations as $nomination) {
            $output->write("{$nomination->getName()} by {$nomination->getAuthor()}", true);
            $channel->addReaction($nomination, '🔼');
        }
    }
}
