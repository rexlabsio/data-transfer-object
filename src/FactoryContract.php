<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

interface FactoryContract
{
    /**
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once
     *
     * @param string $class
     * @return DTOMetadata
     */
    public function getClassMetadata(string $class): DTOMetadata;

    /**
     * @param string $class
     * @param PropertyType[] $propertyTypes
     * @param mixed[] $parameters
     * @param int $flags
     *
     * @return DataTransferObject
     */
    public function make(
        string $class,
        array $propertyTypes,
        array $parameters,
        int $flags = NONE
    ): DataTransferObject;
}
