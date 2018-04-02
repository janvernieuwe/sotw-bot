<?php

namespace App\Command\Yasmin;

use App\Yasmin\Event\MessageReceivedEvent;
use App\Yasmin\Event\ReactionAddedEvent;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\MessageReaction;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
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
        $adminRole = $container->getParameter('adminRole');
        $permissionsRole = $container->getParameter('permissionsRole');
        $dispatcher = $container->get('event_dispatcher');
        $io = new SymfonyStyle($input, $output);
        $loop = Factory::create();
        $client = new Client([], $loop);

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
            function (Message $message) use ($io, $dispatcher, $adminRole, $permissionsRole) {
                // Don't listen to bots (and myself)
                if ($message->author->bot) {
                    return;
                }
                $io->writeln(
                    'Received Message from '.$message->author->tag.' in '.
                    ($message->channel->type === 'text' ? 'channel #'.$message->channel->name : 'DM').' with '
                    .$message->attachments->count().' attachment(s) and '.\count($message->embeds).' embed(s)'
                );
                $event = new MessageReceivedEvent($message, $io, $adminRole, $permissionsRole);
                $dispatcher->dispatch(MessageReceivedEvent::NAME, $event);
            }
        );

        $client->on(
            'messageReactionAdd',
            function (MessageReaction $reaction) use ($dispatcher, $io, $adminRole) {
                if ($reaction->me) {
                    return;
                }
                $users = $reaction->users->all();
                $usernames = [];
                foreach ($users as $id => $user) {
                    $usernames[$id] = $user->username;
                }
                /** @noinspection PhpUndefinedFieldInspection */
                $output = 'Received messageReactionAdd '.$reaction->emoji->name.' from '
                    .implode(', ', $usernames).' in channel #'.$reaction->message->channel->name;
                $io->writeln($output);
                $event = new ReactionAddedEvent($reaction, $io, $adminRole);
                $dispatcher->dispatch(ReactionAddedEvent::NAME, $event);
            }
        );

        $client->login($container->getParameter('token'));
        $loop->run();
    }
}
