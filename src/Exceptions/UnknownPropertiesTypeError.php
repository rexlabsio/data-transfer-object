<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class UnknownPropertiesTypeError
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class UnknownPropertiesTypeError extends DataTransferObjectTypeError
{
    /**
     * UnknownPropertiesError constructor.
     *
     * @param string $class
     * @param array $propertyNames
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $class, array $propertyNames, int $code = 0, Throwable $previous = null)
    {
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
    public function buildMessage(string $class, array $propertyNames): string
    {
        $classParts = explode('\\', $class);

        return sprintf(
            'Unknown %s `%s` for %s',
            count($propertyNames) === 1 ? 'property' : 'properties',
            implode('`, `', $propertyNames),
            end($classParts)
        );
    }
}
