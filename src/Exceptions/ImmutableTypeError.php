<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class ImmutableTypeError
 *
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class ImmutableTypeError extends DataTransferObjectTypeError
{
    /**
     * ImmutableError constructor.
     *
     * @param string $class
     * @param string $name
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $class,
        string $name,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $this->buildMessage($class, $name),
            $code,
            $previous
        );
    }

    /**
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    private function buildMessage(string $class, string $name): string
    {
        $classParts = explode('\\', $class);

        return sprintf(
            'Immutable type: Cannot change the value of property %s on immutable %s',
            $name,
            end($classParts)
        );
    }
}
