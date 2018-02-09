<?php

namespace App\Command\Rewatch;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SayCommand
 * @package App\Command
 */
class ValidateCommand extends ContainerAwareCommand
{
    use DisplayRewatchNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:rewatch:validate')
            ->setDescription('Validate the rewatch nominations')
            ->setHelp('Checks if shows are valid')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Removes invalid nominations');
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
        $delete = $input->hasParameterOption('--delete');
        $io->section('Fetching MAL data');
        $nominations = $channel->getLastNominations();
        $this->displayNominees($io, $nominations);
        if (count($nominations) !== 10) {
            $io->note(sprintf('Wrong amount of nominations (%s/10)', count($nominations)));
        }
        foreach ($nominations as $nomination) {
            $errors = $channel->validate($nomination);
            if (count($errors)) {
                $io->error($nomination->getAuthor().': '.$nomination->getAnime()->title.PHP_EOL.$errors);
                if ($delete) {
                    $channel->removeMessage($nomination->getMessageId());
                    continue;
                }
                $channel->addReaction($nomination, '❌');
                continue;
            }
            $io->success($nomination->getAuthor().': '.$nomination->getAnime()->title);
        }
    }
}
