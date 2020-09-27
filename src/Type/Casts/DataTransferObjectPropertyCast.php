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
     * Each PropertyType's type is passed to `canCastType`. If any return true
     * then this PropertyCast will be attached to the PropertyType
     *
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool
    {
        return is_a($type, DataTransferObject::class, true);
    }

    /**
     * Map raw data to the cast type. If data is not in expected format it has
     * likely been cast to something else in a union type and should be ignored.
     * Simply return the data as is.
     *
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

        // Only attempt to cast if the data is an assoc array
        // If it's indexed then it's far more likely that this property is
        // supposed to be a collection of items eg type: `DTO|DTO[]`
        $isAssoc = true;
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                $isAssoc = false;
                break;
            }
        }

        if (!$isAssoc) {
            return $data;
        }

        return $type::{'make'}($data, $flags);
    }

    /**
     * Map type back to raw data. If property is not the expected type it has
     * likely been cast already and should be ignored.
     * Simply return the property as is.
     *
     * @param string $name
     * @param mixed $property
     * @param int $flags
     *
     * @return mixed
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
