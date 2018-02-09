<?php

namespace App\Command\Rewatch;

use App\Channel\Channel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SayCommand
 * @package App\Command
 */
class FinishCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:rewatch:finish')
            ->setDescription('Count the votes and announce the winner')
            ->setHelp('Counts the votes and announces the winner')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dont actually change anything');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $channel = $this->getContainer()->get('discord.channel.rewatch');
        $io->section('Fetch nomination data');
        $nominations = $channel->getValidNominations();
        $this->displayNominees($io, $nominations);
        $dryRun = $input->hasParameterOption('--dry-run');

        if (count($nominations) !== 10) {
            if (!$dryRun) {
                throw new \RuntimeException('Invalid number of nominees '.count($nominations));
            }
            $io->note('Invalid number of nominees '.count($nominations));
        }
        if ($nominations[0]->getVotes() === $nominations[1]->getVotes()) {
            if (!$dryRun) {
                throw new \RuntimeException('There is no clear winner');
            }
            $io->note('There is no clear winner');
        }
        if ($dryRun) {
            return;
        }

        $winner = $nominations[0];
        $io->section('Send message');
        $channel->message(
            sprintf(
                ':trophy: Deze rewatch kijken we naar %s (%s), genomineerd door <@!%s>',
                $winner->getAnime()->title,
                $winner->getContent(),
                $winner->getAuthorId()
            )
        );

        $io->section('Set channel permissions');
        $channel->allow($this->getContainer()->getParameter('permissions_role'), Channel::ROLE_SEND_MESSAGES);
    }
}
