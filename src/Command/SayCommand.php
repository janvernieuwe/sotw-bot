<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SayCommand
 * @package App\Command
 */
class SayCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:sotw:say')
            ->setDescription('Say something in the sotw channel')
            ->setHelp('Say something as the bot in the song of the week channel')
            ->addArgument('message', InputArgument::REQUIRED, 'The message');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $discord = $this->getContainer()->get('discord');
        $discord->channel->createMessage(
            [
                'channel.id' => $this->getContainer()->getParameter('channel_id'),
                'content'    => $message = $input->getArgument('message'),
            ]
        );
        $io->writeln('BOT: '.$message);
    }
}
