<?php

namespace Rexlabs\DataTransferObject\Type\Casts;

use InvalidArgumentException;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Type\PropertyCast;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\WITH_DEFAULTS;

class DataTransferObjectPropertyCast implements PropertyCast
{
    /**
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool
    {
        return is_a($type, DataTransferObject::class, true);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function shouldCastValue($value): bool
    {
        return is_array($value);
    }

    /**
     * @param mixed $property
     *
     * @return bool
     */
    public function shouldMapToData($property): bool
    {
        return $property instanceof DataTransferObject;
    }

    /**
     * @param string $name
     * @param mixed $data
     * @param string $type
     * @param int $flags
     *
     * @return DataTransferObject
     * @uses DataTransferObject::__construct();
     */
    public function castToType(string $name, $data, string $type, int $flags = NONE): DataTransferObject
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('Type %s requires data of type array', $type));
        }

        return $type::{'make'}($data, $flags);
    }

    /**
     * @param string $name
     * @param mixed $property
     * @param int $flags
     *
     * @return array
     */
    public function toData(string $name, $property, int $flags = NONE): array
    {
        if (!$property instanceof DataTransferObject) {
            throw new InvalidArgumentException(
                sprintf(
                    'Property %s is invalid for type %s',
                    $name,
                    $property
                )
            );
        }

        return $flags & WITH_DEFAULTS
            ? $property->toArrayWithDefaults()
            : $property->toArray();
    }
}
