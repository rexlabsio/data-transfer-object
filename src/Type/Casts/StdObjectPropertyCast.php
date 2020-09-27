<?php

namespace Rexlabs\DataTransferObject\Type\Casts;

use Rexlabs\DataTransferObject\Type\PropertyCast;
use stdClass;

use const Rexlabs\DataTransferObject\NONE;

class StdObjectPropertyCast implements PropertyCast
{
    /**
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool
    {
        return is_a($type, stdClass::class, true);
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

        return (object)$data;
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
        if (!$property instanceof stdClass) {
            return $property;
        }

        return (array)$property;
    }
}
