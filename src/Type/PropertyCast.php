<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type;

use const Rexlabs\DataTransferObject\NONE;

interface PropertyCast
{
    /**
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool;

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function shouldCastValue($value): bool;

    /**
     * @param mixed $property
     *
     * @return bool
     */
    public function shouldMapToData($property): bool;

    /**
     * @param string $name
     * @param mixed $data
     * @param string $type
     * @param int $flags
     *
     * @return mixed
     */
    public function castToType(string $name, $data, string $type, int $flags = NONE);

    /**
     * @param string $name
     * @param mixed $property
     * @param int $flags
     *
     * @return mixed
     */
    public function toData(string $name, $property, int $flags = NONE);
}
