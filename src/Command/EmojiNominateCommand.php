<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EmojiNominateCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:server')
            ->setDescription('Nominates the server emoji')
            ->setHelp('Shows all the server emoji in the channel');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $server = $this->getContainer()->get('discord.server');
        $channel = $this->getContainer()->get('discord.channel.emoji');
        $emojis = $server->getEmojis();
        $io->section('Add server emoji nominations');
        foreach ($emojis as $emoji) {
            $uri = sprintf('https://cdn.discordapp.com/emojis/%s.png?v=1', $emoji->id);
            $io->write("Adding {$emoji->name}: $uri", true);
            $channel->embedImage($uri, $emoji->name);
        }
    }
}
