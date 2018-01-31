<?php

namespace App\Command;

use App\Channel\EmojiChannel;
use App\Guild;
use App\Message\EmojiNomination;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:import')
            ->setDescription('Import the winning emoji into the server')
            ->setHelp('Adds the winning emoji to the server, this replaces the least favorites')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show console output');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dryRun = $input->hasParameterOption('--dry-run');
        $this->io = new SymfonyStyle($input, $output);
        $this->server = $this->getContainer()->get('discord.server');
        $this->channel = $this->getContainer()->get('discord.channel.emoji');
        $messages = $this->channel->getNominations();
        $messages = $this->channel->sortByVotes($messages);
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
}
