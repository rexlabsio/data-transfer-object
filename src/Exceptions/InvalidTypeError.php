<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class InvalidTypeError
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class InvalidTypeError extends DataTransferObjectError
{
    /**
     * InvalidTypeError constructor.
     *
     * @param string $name
     * @param array $types
     * @param $value
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $name,
        array $types,
        $value,
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            $this->buildMessage($name, $types, $value),
            $code,
            $previous
        );
    }

    /**
     * @param string $name
     * @param array $types
     * @param mixed $value
     * @return string
     */
    private function buildMessage(string $name, array $types, $value): string
    {
        if ($value === null) {
            $value = 'null';
        }

        if (is_object($value)) {
            $value = get_class($value);
        }

        if (is_array($value)) {
            $value = 'array';
        }

        $expectedTypes = implode(', ', $types) ?: 'unknown';

        $currentType = gettype($value);

        return sprintf(
            'Invalid type: expected "%s" to be of type %s, instead got value `%s` (%s).',
            $name,
            $expectedTypes,
            $value,
            $currentType
        );
    }
}
