<?php

namespace App\Command\Rewatch;

use App\Channel\Channel;
use PHP_CodeSniffer\Tokenizers\PHP;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SayCommand
 * @package App\Command
 */
class OpenNominationsCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:rewatch:open')
            ->setDescription('Open the rewatch nominations')
            ->setHelp('Opens the rewatch nominations');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $channel = $this->getContainer()->get('discord.channel.rewatch');
        $io->write('Set permissions', true);
        $channel->allow($this->getContainer()->getParameter('permissions_role'), Channel::ROLE_SEND_MESSAGES);
        $io->write('Send message', true);
        $channel->message('Bij deze zijn de nominaties voor de rewatch geopend! :tv:');
    }
}
