<?php

namespace App\Command;

use GrumPHP\Exception\RuntimeException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class EmojiImportCommand
 * @package App\Command
 */
class EmojiSelfReactCommand extends ContainerAwareCommand
{

    use DisplayEmojiNomineesTrait;

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $server = $this->getContainer()->get('discord.server');
        $channel = $this->getContainer()->get('discord.channel.emoji');
        $serverEmojis = $server->getEmojis();
        $messages = $channel->getNominations();
        if (count($serverEmojis) === 50) {
            throw new RuntimeException('Bot needs a free slot to cycle emoji\'s in');
        }
        foreach ($messages as $message) {
            try {
                $io->section($message->getName());
                $io->write('add emoji', true);
                $emoji = $message->isGuildNomination() ?
                    $server->getEmojiByName($message->getName()) :
                    $server->addEmojiFromNomination($message);
                $io->write('add reaction', true);
                $channel->addReaction($message, "{$emoji->name}:{$emoji->id}");
            } catch (\Exception $e) {
                $io->error("Failed to add {$message->getName()}".PHP_EOL.$e->getMessage());
                sleep(5);
            }
            if ($message->isUserNomination()) {
                $server->removeEmoji($emoji->id);
                $io->write("Removed {$emoji->name} reaction", true);
            }
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:preview')
            ->setDescription('Import the winning emoji into the server')
            ->setHelp('Temp creates emoji to add to the nomination itself')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show console output');
    }
}
