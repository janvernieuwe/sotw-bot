<?php

namespace App\Command\Yasmin;

use App\Formatter\BBCodeFormatter;
use App\Yasmin\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class RunCommand
 * @package App\Command
 */
class RunCommand extends ContainerAwareCommand
{

    protected function configure(): void
    {
        $this
            ->setName('haamc:yasmin:run')
            ->setDescription('Run the main yasmin loop')
            ->setHelp('Interactive botness');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $dispatcher = $container->get('event_dispatcher');
        $io = new SymfonyStyle($input, $output);
        $loop = \React\EventLoop\Factory::create();
        $client = new \CharlotteDunois\Yasmin\Client(array(), $loop);

        $client->on(
            'ready',
            function () use ($client, $io) {
                $io->writeln(
                    'Logged in as '.$client->user->tag.' created on '.$client->user->createdAt->format(
                        'd.m.Y H:i:s'
                    )
                );
            }
        );

        $client->on(
            'message',
            function (Message $message) use ($io, $dispatcher) {
                $io->writeln(
                    'Received Message from '.$message->author->tag.' in '.
                    ($message->channel->type === 'text' ? 'channel #'.$message->channel->name : 'DM').' with '
                    .$message->attachments->count().' attachment(s) and '.\count($message->embeds).' embed(s)'
                );

                // Don't listen to bots (and myself)
                if ($message->author->bot) {
                    return;
                }

                $event = new MessageReceivedEvent($message, $io);
                $dispatcher->dispatch(MessageReceivedEvent::NAME, $event);
            }
        );

        $client->login($container->getParameter('token'));
        $loop->run();
    }
}
