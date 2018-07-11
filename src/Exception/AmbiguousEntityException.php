<?php

namespace App\Exception;

/**
 * Class AmbiguousEntityException
 *
 * @package App\Exception
 */
class AmbiguousEntityException extends \Exception
{
    /**
     * @param string $entity
     * @param string $id
     *
     * @return AmbiguousEntityException
     */
    public static function create(string $entity, string $id): AmbiguousEntityException
    {
        return new self(sprintf('Ambigious entity %s with id %s', $entity, $id));
    }
}
