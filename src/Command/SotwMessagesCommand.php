<?php

namespace App\Command;

use App\Message\Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class SotwValidateCommand
 * @package App\Command
 */
class SotwMessagesCommand extends ContainerAwareCommand
{
    use DisplayNomineesTrait;

    protected function configure(): void
    {
        $this
            ->setName('haamc:sotw:messages')
            ->setDescription('Test command to get alot of messages (over 100)')
            ->setHelp('Shows alot of messages');
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
        $messages = $sotw->getManyMessages(500);
        $headers = ['id', 'date', 'name', 'message'];
        $data = [];
        foreach ($messages as $i => $message) {
            $message = new Message($message);
            $data[] = [
                $i,
                $message->getDate()->format('Y-m-d H:i:s'),
                $message->getAuthor(),
                substr(str_replace(PHP_EOL, '', $message->getContent()), 0, 100),
            ];
        }
        $io->table($headers, $data);
    }
}
