<?php

namespace App\Util;

use CharlotteDunois\Yasmin\Models\Emoji;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class Util
{
    public static function instantiate(string $class, array $properties)
    {
        $instance = new $class();
        foreach ($properties as $property => $value) {
            if (property_exists($class, $property)) {
                $instance->$property = $value;
            }
        }

        return $instance;
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public static function mention(int $userId): string
    {
        return sprintf('<@!%s>', $userId);
    }

    /**
     * @param int $channelId
     *
     * @return string
     */
    public static function channelLink(int $channelId): string
    {
        return sprintf('<#%s>', $channelId);
    }

    /**
     * @return \DateTime
     */
    public static function getCurrentDate(): \DateTime
    {
        $time = new \DateTime();
        $time->setTimezone(new \DateTimeZone('Europe/Brussels'));

        return $time;
    }

    /**
     * @param ConstraintViolationListInterface $errorList
     *
     * @param string                           $glue
     *
     * @return string
     */
    public static function errorsToString(ConstraintViolationListInterface $errorList, $glue = "\n"): string
    {
        $errors = [];
        /** @var ConstraintViolationInterface $error */
        foreach ($errorList as $error) {
            $errors[] = $error->getMessage();
        }

        return implode($glue, $errors);
    }

    /**
     * @param Emoji $emoji
     *
     * @return string
     */
    public static function emojiToString(Emoji $emoji): string
    {
        return sprintf('<:%s:%s>', $emoji->name, $emoji->id);
    }

    /**
     * @param Emoji $emoji
     *
     * @return string
     */
    public static function animatedEmojiToString(Emoji $emoji): string
    {
        return sprintf('<a:%s:%s>', $emoji->name, $emoji->id);
    }
}
