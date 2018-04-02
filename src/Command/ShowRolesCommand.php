<?php

namespace App\Command;

use RestCord\DiscordClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ShowRolesCommand
 * @package App\Command
 */
class ShowRolesCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('haamc:roles:show')
            ->setDescription('Show a list of roles on a server')
            ->setHelp('Shows a list of ids and names of roles to help configure the app')
            ->addArgument('server', InputArgument::REQUIRED, 'Snowflake of the server');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $discord = $this->getContainer()->get(DiscordClient::class);
        $roles = $discord->guild->getGuildRoles(
            [
                'guild.id' => (int)$input->getArgument('server'),
            ]
        );
        $headers = ['name', 'id'];
        $data = [];
        foreach ($roles as $role) {
            $data[] = [$role->name, $role->id];
        }

        $io->table($headers, $data);
    }
}
