<?php

namespace Rexlabs\DataTransferObject\Type\Casts;

use ArrayObject;
use Rexlabs\DataTransferObject\Type\PropertyCast;

use const Rexlabs\DataTransferObject\NONE;

class ArrayObjectPropertyCast implements PropertyCast
{
    /**
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool
    {
        return is_a($type, ArrayObject::class, true);
    }

    /**
     * @param string $name
     * @param mixed $data
     * @param string $type
     * @param int $flags
     *
     * @return mixed
     */
    public function toType(string $name, $data, string $type, int $flags = NONE)
    {
        if (!is_array($data)) {
            return $data;
        }

        return new ArrayObject($data);
    }

    /**
     * @param string $name
     * @param mixed $property
     * @param int $flags
     *
     * @return mixed
     */
    public function toData(string $name, $property, int $flags = NONE)
    {
        if (!$property instanceof ArrayObject) {
            return $property;
        }

        return (array) $property;
    }
}
