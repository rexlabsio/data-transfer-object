<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Illuminate\Support\Str;
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
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(array $properties, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Non-nullable %s %s has not been initialised.',
                Str::plural('property', count($properties)),
                implode('`, `', array_map(function (string $property): string {
                    return '"' . $property . '"';
                }, $properties))
            ),
            $code,
            $previous
        );
    }
}
