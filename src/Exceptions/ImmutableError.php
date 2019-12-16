<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class ImmutableError
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class ImmutableError extends DataTransferObjectError
{
    /**
     * ImmutableError constructor.
     *
     * @param string $name
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $name, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Cannot change the value of property %s on an immutable data transfer object',
                $name
            ),
            $code,
            $previous
        );
    }
}
