<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class UninitialisedPropertiesError
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class UninitialisedPropertiesError extends DataTransferObjectError
{
    /**
     * UninitialisedPropertiesError constructor.
     *
     * @param array $properties
     * @param string $objectClass
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        array $properties,
        string $objectClass,
        int $code = 0,
        Throwable $previous = null
    ) {
        $classParts = explode('\\', $objectClass);
        parent::__construct(
            sprintf(
                '%s %s from %s %s not been initialised.',
                count($properties) === 1 ? 'Property' : 'Properties',
                implode('`, `', array_map(function (string $property): string {
                    return '"' . $property . '"';
                }, $properties)),
                end($classParts),
                count($properties) === 1 ? 'has' : 'have'
            ),
            $code,
            $previous
        );
    }
}
