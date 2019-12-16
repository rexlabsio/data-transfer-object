<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Illuminate\Support\Str;
use Throwable;

/**
 * Class UnknownPropertiesError
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class UnknownPropertiesError extends DataTransferObjectError
{
    /**
     * UnknownPropertiesError constructor.
     *
     * @param array $properties
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(array $properties, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Public %s `%s` not found',
                Str::plural('property', count($properties)),
                implode('`, `', $properties)
            ),
            $code,
            $previous
        );
    }
}
