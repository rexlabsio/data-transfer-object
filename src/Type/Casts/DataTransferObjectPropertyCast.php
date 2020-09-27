<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type\Casts;

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
     * @param string $name
     * @param mixed $data
     * @param string $type
     * @param int $flags
     *
     * @return mixed|DataTransferObject
     * @uses DataTransferObject::__construct();
     */
    public function toType(string $name, $data, string $type, int $flags = NONE)
    {
        if (!is_array($data)) {
            return $data;
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
            return $property;
        }

        return $flags & WITH_DEFAULTS
            ? $property->toArrayWithDefaults()
            : $property->toArray();
    }
}
