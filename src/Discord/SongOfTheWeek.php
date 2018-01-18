<?php

namespace App\Discord;

use App\Entity\SotwNomination;
use RestCord\DiscordClient;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeek
 * @package App\Discord
 */
class SongOfTheWeek
{
    public const ROLE_SEND_MESSAGES = 0x00000800;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var DiscordClient
     */
    private $discord;

    /**
     * @var string
     */
    private $role;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * SongOfTheWeek constructor.
     * @param DiscordClient $discord
     * @param ValidatorInterface $validator
     * @param string $channelId
     * @param string $role
     */
    public function __construct(DiscordClient $discord, ValidatorInterface $validator, string $channelId, string $role)
    {

        $this->channelId = (int)$channelId;
        $this->discord = $discord;
        $this->role = $role;
        $this->validator = $validator;
    }

    /**
     * @param string $message
     * @return bool
     */
    public function isOpenNominationsMessage(string $message): bool
    {
        return (bool)preg_match('/Bij deze zijn de nominaties voor week/', $message);
    }

    /**
     * @param int $limit
     * @return SotwNomination[]
     */
    public function getLastNominations(int $limit = 10): array
    {
        $messages = $this->discord->channel->getChannelMessages(
            [
                'channel.id' => $this->channelId,
                'limit'      => $limit + 10,
            ]
        );

        $contenders = [];
        foreach ($messages as $message) {
            // Stop parsing when we arrive at the nomination msg
            if ($this->isOpenNominationsMessage($message['content'])) {
                break;
            }
            if (SotwNomination::isContenter($message['content'])) {
                $contenders[] = SotwNomination::fromMessage($message);
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);
        uasort(
            $contenders,
            function (SotwNomination $a, SotwNomination $b) {
                return $a->getVotes() < $b->getVotes();
            }
        );

        return array_values($contenders);
    }

    /**
     * @param SotwNomination $nomination
     */
    public function announceWinner(SotwNomination $nomination): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $this->createWinningMessage($nomination),
            ]
        );
    }

    /**
     * @param SotwNomination $nomination
     * @return string
     */
    public function createWinningMessage(SotwNomination $nomination): string
    {
        return sprintf(
            ":first_place: De winnaar van week %s is: %s - %s (%s) door <@!%s>\n",
            (int)date('W'),
            $nomination->getArtist(),
            $nomination->getTitle(),
            $nomination->getAnime(),
            $nomination->getAuthorId()
        );
    }

    /**
     *
     */
    public function openNominations(): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $this->createOpenNominationsMessage(),
            ]
        );
        $this->discord->channel->editChannelPermissions(
            [
                'channel.id'   => $this->channelId,
                'overwrite.id' => $this->role,
                'allow'        => self::ROLE_SEND_MESSAGES,
                'type'         => 'role',
            ]
        );
    }

    /**
     * @return string
     */
    public function createOpenNominationsMessage(): string
    {
        $message = <<<MESSAGE
:musical_note: :musical_note: Bij deze zijn de nominaties voor week %s geopend! :musical_note: :musical_note:

Nomineer volgens onderstaande template (copieer en plak deze, en zet er dan de gegevens in):
```
artist: 
title: 
anime:  
url: 
```
MESSAGE;

        return sprintf($message, date('W') + 1);
    }

    /**
     *
     */
    public function closeNominations(): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $this->createCloseNominationsMessage(),
            ]
        );
        $this->discord->channel->editChannelPermissions(
            [
                'channel.id'   => $this->channelId,
                'overwrite.id' => $this->role,
                'deny'         => self::ROLE_SEND_MESSAGES,
                'type'         => 'role',
            ]
        );
    }

    /**
     * @return string
     */
    public function createCloseNominationsMessage(): string
    {
        return 'Laat het stemmen beginnen! :checkered_flag:';
    }

    /**
     * @param SotwNomination $nomination
     * @param string $emoji
     */
    public function addReaction(SotwNomination $nomination, string $emoji): void
    {
        if ($nomination->hasReaction($emoji)) {
            return;
        }
        $this->discord->channel->createReaction(
            [
                'channel.id' => $this->channelId,
                'message.id' => $nomination->getMessageId(),
                'emoji'      => $emoji,
            ]
        );
        sleep(1);
    }

    /**
     * @param SotwNomination $nomination
     * @param string $emoji
     */
    public function removeReaction(SotwNomination $nomination, string $emoji): void
    {
        if (!$nomination->hasReaction($emoji)) {
            return;
        }
        $this->discord->channel->deleteOwnReaction(
            [
                'channel.id' => $this->channelId,
                'message.id' => $nomination->getMessageId(),
                'emoji'      => $emoji,
            ]
        );
        sleep(1);
    }

    /**
     * @param SotwNomination[] $nominees
     * @param bool $throwException
     * @return array
     */
    public function validateNominees(array $nominees, bool $throwException = true): array
    {
        $errors = [];
        foreach ($nominees as $nominee) {
            $tmpErr = $this->validate($nominee);
            if (\count($tmpErr)) {
                /** @noinspection PhpToStringImplementationInspection */
                $errors[] = $nominee.PHP_EOL.$tmpErr;
            }
        }
        $hasErrors = \count($errors);
        if ($throwException && $hasErrors) {
            throw new RuntimeException("[ERROR] Invalid nominations: \n\n".implode(PHP_EOL, $errors));
        }

        return $errors;
    }

    /**
     * @param SotwNomination $nomination
     * @return ConstraintViolationListInterface
     */
    public function validate(SotwNomination $nomination): ConstraintViolationListInterface
    {
        return $this->validator->validate($nomination);
    }

    /**
     * @param SotwNomination $nomination
     * @return bool
     */
    public function isValid(SotwNomination $nomination): bool
    {
        return \count($this->validate($nomination)) === 0;
    }
}
