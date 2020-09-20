<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

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
                'Unknown %s `%s`',
                count($properties) === 1 ? 'property' : 'properties',
                implode('`, `', $properties)
            ),
            $code,
            $previous
        );
    }
}