<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use TypeError;

class DataTransferObjectError extends TypeError
{
    public static function unknownProperties(array $properties): DataTransferObjectError
    {
        $propertyNames = implode('`, `', $properties);

        return new self("Public properties `{$propertyNames}` not found");
    }

    public static function invalidType(string $name, Property $property, $value): DataTransferObjectError
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

        return new self("Invalid type: expected {$name} to be of type"
            . " {$expectedTypes}, instead got value `{$value}` ({$currentType}).");
    }

    public static function uninitialized(string $name): DataTransferObjectError
    {
        return new self("Non-nullable property {$name} has not been initialized.");
    }

    public static function immutable(string $name): DataTransferObjectError
    {
        return new self("Cannot change the value of property {$name} on an immutable data transfer object");
    }
}
