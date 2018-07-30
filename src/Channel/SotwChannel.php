<?php

namespace App\Channel;

use App\Message\SotwNomination;
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

//    /**
//     * @var int
//     */
//    private $channelId;

//    /**
//     * SongOfTheWeek constructor.
//     *
//     * @param int                $channelId
//     * @param ValidatorInterface $validator
//     * @param int                $roleId
//     */
//    public function __construct(
//        int $channelId,
//        ValidatorInterface $validator,
//        int $roleId
//    ) {
//        $this->roleId = $roleId;
//        $this->validator = $validator;
//        $this->channelId = $channelId;
//    }
//
//
//    /**
//     * @param SotwNomination[] $nominations
//     */
//    public function addMedals(array $nominations): void
//    {
//        // Only one first place
//        $nomination = array_shift($nominations);
//        $this->addReaction($nomination, self::EMOJI_FIRST_PLACE);
//        $this->addPlaceMedal($nominations, self::EMOJI_SECOND_PLACE);
//        $this->addPlaceMedal($nominations, self::EMOJI_THIRD_PLACE);
//    }
//
//    /**
//     * @param SotwNomination[] $nominations
//     * @param string           $emoji
//     */
//    public function addPlaceMedal(array &$nominations, string $emoji): void
//    {
//        if (!count($nominations)) {
//            return;
//        }
//        $nomination = array_shift($nominations);
//        $score = $nomination->getVotes();
//        while ($nomination !== null && $nomination->getVotes() === $score) {
//            $this->addReaction($nomination, $emoji);
//            $nomination = array_shift($nominations);
//        }
//        // Put things back where they belong
//        if ($nomination !== null) {
//            array_unshift($nominations, $nomination);
//        }
//    }
//
//    /**
//     * @return int
//     */
//    public function getChannelId(): int
//    {
//        return $this->channelId;
//    }
}
