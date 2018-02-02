<?php

namespace App\Command;

use App\Message\EmojiNomination;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Class EmojiValidateCommand
 * @package App\Command
 */
class EmojiValidateCommand extends ContainerAwareCommand
{
    use DisplayEmojiNomineesTrait;

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $delete = $input->hasParameterOption('--delete');
        $quiet = $input->hasParameterOption('--silent');
        $server = $this->getContainer()->get('discord.server');
        $channel = $this->getContainer()->get('discord.channel.emoji');
        $validator = $this->getContainer()->get('validator');
        $messages = $channel->getNominations();
        /** @var EmojiNomination[] $nominations */
        $nominations = array_filter(
            $messages,
            function (EmojiNomination $nomination) {
                return $nomination->isUserNomination();
            }
        );
        $this->displayNominees($io, $nominations);
        foreach ($nominations as $nomination) {
            $errors = $validator->validate($nomination);
            if ($server->hasEmoji($nomination->getName())) {
                $violation = new ConstraintViolation(
                    'An emoji already exists with name '.$nomination->getName(),
                    '',
                    [],
                    $nomination,
                    '',
                    $nomination->getName()
                );
                $errors->add($violation);
            }
            if (\count($errors)) {
                $io->error($nomination->getName().PHP_EOL.(string)$errors);
                if ($delete) {
                    $channel->removeMessage($nomination->getMessageId());
                    $io->write($nomination->getName().' has been removed', true);
                    continue;
                }
                if (!$quiet) {
                    $channel->addReaction($nomination, '❌');
                }
                continue;
            }
            $io->write('[VALID] '.$nomination->getName(), true);
            if (!$quiet) {
                $channel->removeReaction($nomination, '❌');
                //$channel->addReaction($nomination, '🆗');
            }
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('haamc:emoji:validate')
            ->setDescription('Validates the user nominations')
            ->setHelp('Adds a reaction to bad nomination')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Removes invalid nominations')
            ->addOption('silent', null, InputOption::VALUE_NONE, 'Shht');
    }
}
