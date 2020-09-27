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
     * @param string $name
     * @param mixed $data
     * @param string $type
     * @param int $flags
     *
     * @return mixed
     */
    public function toType(string $name, $data, string $type, int $flags = NONE);

    /**
     * @param string $name
     * @param mixed $property
     * @param int $flags
     *
     * @return mixed
     */
    public function toData(string $name, $property, int $flags = NONE);
}
