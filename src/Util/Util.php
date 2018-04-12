<?php

namespace App\Util;

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
     * @return string
     */
    public static function mention(int $userId): string
    {
        return sprintf('<@!%s>', $userId);
    }

    /**
     * @param int $channelId
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
}
