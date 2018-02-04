<?php

namespace App\Command;

use App\Channel\EmojiChannel;
use App\Guild;
use App\Message\EmojiNomination;
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
class EmojiImportCommand extends ContainerAwareCommand
{

    use DisplayEmojiNomineesTrait;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var EmojiChannel
     */
    private $channel;

    /**
     * @var Guild
     */
    private $server;

    /**
     * @var bool
     */
    private $dryRun = false;

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
        $this->dryRun = $input->hasParameterOption('--dry-run');
        $statsOnly = $input->hasParameterOption('--stats');
        $this->io = new SymfonyStyle($input, $output);
        $this->server = $this->getContainer()->get('discord.server');
        $this->channel = $this->getContainer()->get('discord.channel.emoji');
        $messages = $this->channel->getNominations();

        /** @var EmojiNomination[] $messages */
        $messages = $this->channel->sortByVotes($messages);
        $totalVotes = 0;
        foreach ($messages as $message) {
            $totalVotes += $message->getVotes();
        }
        $this->io->write(
            sprintf(
                'Total: %s votes, user: %s votes, nominations: %s',
                $totalVotes,
                $totalVotes - count($messages),
                count($messages)
            ),
            true
        );
        if ($statsOnly) {
            return;
        }
        $winners = \array_slice($messages, 0, 50);
        $losers = \array_slice($messages, 50);
        $this->removeLosers($losers);
        $this->addWinners($winners);
    }

    /**
     * @param EmojiNomination[]|array $messages
     */
    public function removeLosers(array $messages): void
    {
        $this->io->section('Remove losers');
        $this->displayNominees($this->io, $messages);
        if ($this->dryRun) {
            return;
        }
        foreach ($messages as $message) {
            if ($this->server->hasEmoji($message->getName())) {
                $this->io->write("Removing {$message->getName()}", true);
                try {
                    $emoji = $this->server->getEmojiByName($message->getName());
                    $this->channel->message(
                        ":put_litter_in_its_place: Removed emoji :{$emoji->name}: <:{$emoji->name}:{$emoji->id}>"
                    );
                    $this->server->removeEmoji($emoji->id);
                } catch (\Exception $e) {
                    $this->io->error("Failed to remove {$message->getName()}".PHP_EOL.$e->getMessage());
                    sleep(5);
                }
            }
        }
    }

    /**
     * @param EmojiNomination[] $messages
     */
    public function addWinners(array $messages): void
    {
        $this->io->section('Add winners');
        $this->displayNominees($this->io, $messages);
        if ($this->dryRun) {
            return;
        }
        foreach ($messages as $message) {
            if (!$this->server->hasEmoji($message->getName())) {
                $this->io->write("Adding {$message->getName()}", true);
                try {
                    $emoji = $this->server->addEmojiFromNomination($message);
                    $this->channel->message(":new: Added emoji :{$emoji->name}: <:{$emoji->name}:{$emoji->id}>");
                } catch (\Exception $e) {
                    $this->io->error("Failed to add {$message->getName()}".PHP_EOL.$e->getMessage());
                    sleep(5);
                }
            }
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:import')
            ->setDescription('Import the winning emoji into the server')
            ->setHelp('Adds the winning emoji to the server, this replaces the least favorites')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show console output')
            ->addOption('stats', null, InputOption::VALUE_NONE, 'Only show quick stats');
    }
}
