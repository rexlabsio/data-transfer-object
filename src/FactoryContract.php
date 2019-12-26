<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Rexlabs\DataTransferObject\Exceptions\DataTransferObjectError;

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

    /**
     * Make Record with $propertyNames of type $class
     *
     * @param string $class
     * @param array $parameters
     * @param array $propertyNames
     * @param int $flags
     * @return DataTransferObject
     */
    public function makeRecord(
        string $class,
        array $parameters,
        array $propertyNames,
        int $flags
    ): DataTransferObject;

    /**
     * Make DTO with only named properties
     * `Pick<$class, $propertyNames>`
     *
     * @param string $class
     * @param array $parameters
     * @param array $propertyNames
     * @param int $flags
     * @return DataTransferObject
     */
    public function makePick(
        string $class,
        array $parameters,
        array $propertyNames,
        int $flags
    ): DataTransferObject;

    /**
     * Make DTO excluding named properties
     * eg `Omit<$class, $propertyNames>`
     *
     * @param string $class
     * @param array $parameters
     * @param array $propertyNames
     * @param int $flags
     * @return DataTransferObject
     */
    public function makeOmit(
        string $class,
        array $parameters,
        array $propertyNames,
        int $flags
    ): DataTransferObject;

    /**
     * @param string $class
     * @param string $excludeClass
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public function makeExclude(
        string $class,
        string $excludeClass,
        array $parameters,
        int $flags
    ): DataTransferObject;

    /**
     * @param string $class
     * @param string $extractClass
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public function makeExtract(
        string $class,
        string $extractClass,
        array $parameters,
        int $flags
    ): DataTransferObject;
}
