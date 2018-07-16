<?php

namespace App\Channel;

use App\Message\SotwNomination;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeek
 *
 * @package App\Discord
 * @deprecated
 */
class SotwChannel extends Channel
{
    public const EMOJI_FIRST_PLACE = '🥇';
    public const EMOJI_SECOND_PLACE = '🥈';
    public const EMOJI_THIRD_PLACE = '🥉';

    /**
     * @var int
     */
    private $roleId;

    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var int
     */
    private $channelId;

    /**
     * SongOfTheWeek constructor.
     *
     * @param int                $channelId
     * @param ValidatorInterface $validator
     * @param int                $roleId
     */
    public function __construct(
        int $channelId,
        ValidatorInterface $validator,
        int $roleId
    ) {
        $this->roleId = $roleId;
        $this->validator = $validator;
        $this->channelId = $channelId;
    }

    /**
     * @param int $limit
     *
     * @return SotwNomination[]
     */
    public function getLastNominations(int $limit = 10): array
    {
        $messages = $this->getMessages($limit + 10);
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

        return $this->sortByVotes($contenders);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function isOpenNominationsMessage(string $message): bool
    {
        return (bool)preg_match('/Bij deze zijn de nominaties voor week/', $message);
    }

    /**
     * @param SotwNomination $nomination
     */
    public function announceWinner(SotwNomination $nomination): void
    {
        $this->message(
            sprintf(
                ":trophy: De winnaar van week %s is: %s - %s (%s) door <@!%s>\n",
                (int)date('W'),
                $nomination->getArtist(),
                $nomination->getTitle(),
                $nomination->getAnime(),
                $nomination->getAuthorId()
            )
        );
    }

    /**
     *
     */
    public function openNominations(): void
    {
        $this->message($this->createOpenNominationsMessage());
        $this->allow($this->roleId, self::ROLE_SEND_MESSAGES);
    }

    /**
     * @return string
     */
    public function createOpenNominationsMessage(): string
    {
        $message = <<<MESSAGE
:musical_note: :musical_note: Bij deze zijn de nominaties voor week %s geopend! :musical_note: :musical_note:

Nomineer volgens onderstaande template (kopieer en plak deze, en zet er dan de gegevens in):
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
        $this->message('Laat het stemmen beginnen! :checkered_flag:');
        $this->deny($this->roleId, self::ROLE_SEND_MESSAGES);
    }

    /**
     * @param SotwNomination[] $nominations
     */
    public function addMedals(array $nominations): void
    {
        // Only one first place
        $nomination = array_shift($nominations);
        $this->addReaction($nomination, self::EMOJI_FIRST_PLACE);
        $this->addPlaceMedal($nominations, self::EMOJI_SECOND_PLACE);
        $this->addPlaceMedal($nominations, self::EMOJI_THIRD_PLACE);
    }

    /**
     * @param SotwNomination[] $nominations
     * @param string           $emoji
     */
    public function addPlaceMedal(array &$nominations, string $emoji): void
    {
        if (!count($nominations)) {
            return;
        }
        $nomination = array_shift($nominations);
        $score = $nomination->getVotes();
        while ($nomination !== null && $nomination->getVotes() === $score) {
            $this->addReaction($nomination, $emoji);
            $nomination = array_shift($nominations);
        }
        // Put things back where they belong
        if ($nomination !== null) {
            array_unshift($nominations, $nomination);
        }
    }

    /**
     * @param SotwNomination[] $nominees
     * @param bool             $throwException
     *
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
     *
     * @return ConstraintViolationListInterface
     */
    public function validate(SotwNomination $nomination): ConstraintViolationListInterface
    {
        return $this->validator->validate($nomination);
    }

    /**
     * @param SotwNomination $nomination
     *
     * @return bool
     */
    public function isValid(SotwNomination $nomination): bool
    {
        return \count($this->validate($nomination)) === 0;
    }

    /**
     * @return int
     */
    public function getChannelId(): int
    {
        return $this->channelId;
    }
}
