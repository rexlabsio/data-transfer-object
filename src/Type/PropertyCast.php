<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type;

use const Rexlabs\DataTransferObject\NONE;

interface PropertyCast
{
    /**
     * Each PropertyType's type is passed to `canCastType`. If any return true
     * then this PropertyCast will be attached to the PropertyType
     *
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool;

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
    public function toType(string $name, $data, string $type, int $flags = NONE);

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
    public function toData(string $name, $property, int $flags = NONE);
}
