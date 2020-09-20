<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class UndefinedPropertiesTypeError
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class UndefinedPropertiesTypeError extends DataTransferObjectTypeError
{
    /**
     * UndefinedPropertiesError constructor.
     *
     * @param string $class
     * @param array $propertyNames
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $class,
        array $propertyNames,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            $this->buildMessage($class, $propertyNames),
            $code,
            $previous
        );
    }

    /**
     * @param string $class
     * @param array $propertyNames
     *
     * @return string
     */
    private function buildMessage(string $class, array $propertyNames): string
    {
        $classParts = explode('\\', $class);

        return sprintf(
            '%s %s from %s %s not been initialised.',
            count($propertyNames) === 1 ? 'Property' : 'Properties',
            implode('`, `', array_map(function (string $property): string {
                return '"' . $property . '"';
            }, $propertyNames)),
            end($classParts),
            count($propertyNames) === 1 ? 'has' : 'have'
        );
    }
}
