<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

interface FactoryContract
{
    /**
     * Make an instance of the requested DTO
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public function make(string $class, array $parameters, int $flags): DataTransferObject;
}
