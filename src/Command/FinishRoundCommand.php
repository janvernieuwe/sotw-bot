<?php


namespace App\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FinishRoundCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sotw:round:finish')
            ->setDescription('Finish a round of sotw')
            ->setHelp('Counts the votes, announces the winner and opens the chat for new nominations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo $this->getContainer()->getParameter('token');
    }
}
