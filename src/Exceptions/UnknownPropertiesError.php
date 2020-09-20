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
     * @param array $propertyNames
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(array $propertyNames, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Unknown %s `%s`',
                count($propertyNames) === 1 ? 'property' : 'properties',
                implode('`, `', $propertyNames)
            ),
            $code,
            $previous
        );
    }
}
