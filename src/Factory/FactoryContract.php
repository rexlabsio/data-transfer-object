<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Factory;

use Rexlabs\DataTransferObject\ClassData\DTOMetadata;
use Rexlabs\DataTransferObject\Type\PropertyCast;

use const Rexlabs\DataTransferObject\NONE;

interface FactoryContract
{
    /**
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once
     *
     * @param string $class
     *
     * @return DTOMetadata
     */
    public function getClassMetadata(string $class): DTOMetadata;

    /**
     * @param string $class
     * @param string[][] $propertyTypesMap ['property_name' => ['null', 'int']]
     * @param mixed[] $propertyDefaultsMap ['property_name' => 'default_value']
     * @param PropertyCast[]|PropertyCast[][] $classPropertyCastMap
     *                                        either ['property_name' => PropertyCast]
     *                                        or ['property_name' => PropertyCast[]]
     * @param int $flags
     *
     * @return DTOMetadata
     */
    public function setClassMetadata(
        string $class,
        array $propertyTypesMap,
        array $propertyDefaultsMap = [],
        array $classPropertyCastMap = [],
        int $flags = NONE
    ): DTOMetadata;
}
