<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

interface FactoryContract
{
    /**
     * Make an instance of the requested DTO
     *
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public function make(
        string $class,
        array $parameters,
        int $flags
    ): DataTransferObject;

    /**
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once
     *
     * @param string $class
     * @return DTOMetadata
     */
    public function getDTOMetadata(string $class): DTOMetadata;
}
