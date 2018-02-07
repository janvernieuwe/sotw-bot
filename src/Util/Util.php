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
}
