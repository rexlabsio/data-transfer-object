<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Rexlabs\DataTransferObject\Property;
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
     * @param Property $property
     * @param $value
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $name,
        Property $property,
        $value,
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            $this->buildMessage($name, $property, $value),
            $code,
            $previous
        );
    }

    /**
     * @param string $name
     * @param Property $property
     * @param mixed $value
     * @return string
     */
    private function buildMessage(string $name, Property $property, $value): string
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

        $expectedTypes = implode(', ', $property->getTypes());

        $currentType = gettype($value);

        return sprintf(
            'Invalid type: expected %s to be of type %s, instead got value `%s` (%s).',
            $name,
            $expectedTypes,
            $value,
            $currentType
        );
    }
}
