<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartVotingCommand extends ContainerAwareCommand
{
    use DisplayNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:sotw:start')
            ->setDescription('Start the voting for Song Of The Week')
            ->setHelp('Locks the channel and adds an upvote to each nomination');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $sotw = $this->getContainer()->get('song_of_the_week');
        $nominations = $sotw->getLastNominations();
        $this->displayNominees($io, $nominations);

        // Check that we have a clear winner
        if (\count($nominations) !== 10) {
            throw new RuntimeException('Not enough nominations!');
        }

        $io->section('Close nominations');
        $sotw->closeNominations();
        $io->section('Add reactions');
        foreach ($nominations as $nominee) {
            $sotw->addReaction($nominee);
            sleep(1);
        }
    }
}
