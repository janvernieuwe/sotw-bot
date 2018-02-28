<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DmCommand
 * @package App\Command
 */
class DmCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:dm')
            ->setDescription('DM Someone as the bot')
            ->setHelp('Say something as the bot in a DM')
            ->addArgument('id', InputArgument::REQUIRED, 'User ID')
            ->addArgument('message', InputArgument::REQUIRED, 'Message');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $discord = $this->getContainer()->get('discord');
        $channel = $discord->user->createDm(['recipient_id' => (int)$input->getArgument('id')]);
        $discord->channel->createMessage(
            [
                'channel.id' => $channel->id,
                'content'    => $input->getArgument('message'),
            ]
        );
    }
}
