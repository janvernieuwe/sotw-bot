<?php

namespace App\Command\Rewatch;

use App\Channel\Channel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SayCommand
 * @package App\Command
 */
class StartVotingCommand extends ContainerAwareCommand
{
    use DisplayRewatchNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:rewatch:start')
            ->setDescription('Start voting on the nominations')
            ->setHelp('Starts voting on the nominations');
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
        $io->section('Fetch nomination data');
        $nominations = $channel->getValidNominations();
        if (count($nominations) !== 10) {
            throw new \RuntimeException('Invalid number of nominees '.count($nominations));
        }
        $this->displayNominees($io, $nominations);
        $io->section('Set channel permissions');
        $channel->deny($this->getContainer()->getParameter('permissions_role'), Channel::ROLE_SEND_MESSAGES);
        $io->section('Add reactions');
        foreach ($nominations as $nomination) {
            $channel->addReaction($nomination, '🔼');
        }
        $io->section('Send message');
        $channel->message('Laat het stemmen beginnen :checkered_flag: Enkel stemmen als je mee wil kijken!');
        $channel->message('We maken de winnaar zondag namiddag bekend.');
    }
}
