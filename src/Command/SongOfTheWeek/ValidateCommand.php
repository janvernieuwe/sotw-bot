<?php

namespace App\Command\SongOfTheWeek;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SotwValidateCommand
 * @package App\Command
 */
class ValidateCommand extends ContainerAwareCommand
{
    use DisplayNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:sotw:validate')
            ->setDescription('Validate the nominations')
            ->setHelp('Checks if all nominations are valid');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $sotw = $this->getContainer()->get('discord.channel.sotw');
        $errorMessenger = $this->getContainer()->get('discord.dm.sotw');
        $nominations = $sotw->getLastNominations();
        $this->displayNominees($io, $nominations);

        // Check count
        $nominationCount = \count($nominations);
        if ($nominationCount !== 10) {
            $io->note(sprintf('Wrong amount of nominations (%s/10)', $nominationCount));
        }
        if (count($nominations) >= 2 && $nominations[0]->getVotes() === $nominations[1]->getVotes()) {
            $io->note('There is no clear winner!');
        }
        foreach ($nominations as $nomination) {
            $errors = $sotw->validate($nomination);
            if (\count($errors)) {
                $errorMessenger->send($nomination);
                $sotw->addReaction($nomination, '❌');
                $io->error($nomination.PHP_EOL.$errors);
                continue;
            }
            $io->success($nomination);
            $sotw->removeReaction($nomination, '❌');
        }
    }
}