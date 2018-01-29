<?php

namespace App\Command;

use App\Formatter\BBCodeFormatter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FinishRoundCommand
 * @package App\Command
 */
class FinishRoundCommand extends ContainerAwareCommand
{
    use DisplayNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:sotw:finish')
            ->setDescription('Finish a round of Song Of The Week')
            ->setHelp('Counts the votes, announces the winner and opens the chat for new nominations')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force start even without a winner')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dont actually change anything');
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
        $force = $input->hasParameterOption('--force');
        $dryRun = $input->hasParameterOption('--dry-run');
        // When forcing, just only open the nominations
        if ($force) {
            $sotw->openNominations();

            return;
        }

        $this->displayNominees($io, $nominations);
        $sotw->validateNominees($nominations);
        // Validate results
        if (!\count($nominations)) {
            throw new RuntimeException('No nominations found');
        }
        if (!$dryRun && $nominations[0]->getVotes() === $nominations[1]->getVotes()) {
            throw new RuntimeException('There is no clear winner!');
        }

        // Announce the winner and unlock the channel
        $winner = $nominations[0];
        $io->section('Announcing winner');
        $io->write((string)$winner, true);
        if (!$dryRun) {
            $sotw->announceWinner($winner);
            $io->section('Adding medal reactions');
            $sotw->addMedals($nominations);
            $io->section('Open nomination');
            $sotw->openNominations();
        }

        // Output post for the forum
        $io->section('Forum post');
        $formatter = new BBCodeFormatter($nominations);
        $io->write($formatter->createMessage(), true);
    }
}
