<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type;

use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;

use const Rexlabs\DataTransferObject\NONE;

class PropertyType
{
    public const TYPE_ALIASES = [
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'double',
    ];

    /** @var string */
    private $name;

    /** @var array */
    private $types;

    /** @var array */
    private $arrayTypes;

    /**
     * @var PropertyCast[] ['type' => PropertyCast]
     */
    private $typeCasts;

    /**
     * @var PropertyCast[] ['array_type' => PropertyCast]
     */
    private $arrayTypeCasts;

    /** @var bool */
    private $isNullable;

    /** @var bool */
    private $isString;

    /** @var bool */
    private $isInt;

    /** @var bool */
    private $hasValidDefault;

    /** @var mixed */
    private $default;

    /**
     * PropertyType constructor.
     *
     * @param string $name
     * @param array $types
     * @param array $arrayTypes
     * @param array $typeCasts
     * @param array $arrayTypeCasts
     * @param bool $isNullable
     * @param bool $isString
     * @param bool $isInt
     * @param bool $hasValidDefault
     * @param mixed $default
     */
    public function __construct(
        string $name,
        array $types,
        array $arrayTypes,
        array $typeCasts,
        array $arrayTypeCasts,
        bool $isNullable,
        bool $isString,
        bool $isInt,
        bool $hasValidDefault,
        $default
    ) {
        $this->name = $name;
        $this->types = $types;
        $this->arrayTypes = $arrayTypes;
        $this->typeCasts = $typeCasts;
        $this->arrayTypeCasts = $arrayTypeCasts;
        $this->isNullable = $isNullable;
        $this->isString = $isString;
        $this->isInt = $isInt;
        $this->hasValidDefault = $hasValidDefault;
        $this->default = $default;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return array
     */
    public function getArrayTypes(): array
    {
        return $this->arrayTypes;
    }

    /**
     * @return PropertyCast[] ['type' => PropertyCast]
     */
    public function getTypeCasts(): array
    {
        return $this->typeCasts;
    }

    /**
     * @return PropertyCast[] ['array_type' => PropertyCast]
     */
    public function getArrayTypeCasts(): array
    {
        return $this->arrayTypeCasts;
    }

    /**
     * @return array
     */
    public function getAllTypes(): array
    {
        return array_merge(
            $this->types,
            array_map(
                function ($arrayType) {
                    return $arrayType . '[]';
                },
                $this->arrayTypes
            )
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasValidDefault(): bool
    {
        return $this->hasValidDefault;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $value
     *
     * @return PropertyTypeCheck
     */
    public function checkValue($value): PropertyTypeCheck
    {
        return new PropertyTypeCheck(
            $this->name,
            $this->getAllTypes(),
            $value,
            $this->isValidValue($value)
        );
    }

    /**
     * Process value and perform casts
     *
     * @param mixed $value
     * @param int $flags
     *
     * @return mixed
     */
    public function castValueToType($value, int $flags)
    {
        // First cast array items to array types
        // This allows each item to be cast to an array type before the group is
        // cast to a collection type eg ArrayObject|DataTransferObject[]
        if (is_array($value) && count($value) > 0) {
            foreach ($this->getArrayTypeCasts() as $type => $cast) {
                $value = $this->castArrayItemsToType($type, $cast, $value, $flags);
            }
        }

        // Implicit scalar patching - numeric strings can be safely cast to int
        if ($this->isInt && !$this->isString && is_numeric($value)) {
            $value = (int)$value;
        }

        // Check each single value type for possible casts
        foreach ($this->getTypeCasts() as $type => $cast) {
            $value = $cast->toType($this->name, $value, $type, $flags);
        }

        return $value;
    }

    /**
     * @param string $type
     * @param PropertyCast $cast
     * @param array $value
     * @param int $flags
     *
     * @return array
     */
    private function castArrayItemsToType(string $type, PropertyCast $cast, array $value, int $flags): array
    {
        $processedValues = [];
        $typeErrorData = new TypeErrorData('Unknown');

        // Use the cast on each item in the collection
        // Collect nested exception data to rethrow at the end
        foreach ($value as $i => $valueItem) {
            // Catch and adapt exceptions to show nested array index
            // eg user.children.0.first_name
            try {
                $processedValues[$i] = $cast->toType($this->name, $valueItem, $type, $flags);
            } catch (InvalidTypeError $exception) {
                $typeErrorData->mapInvalidTypeData($exception, (string)$i);
            } catch (UnknownPropertiesTypeError $exception) {
                $typeErrorData->mapUnknownData($exception, (string)$i);
            } catch (UndefinedPropertiesTypeError $exception) {
                $typeErrorData->mapUndefinedData($exception, (string)$i);
            }
        }

        // No need to recheck flags since these nested property exceptions
        // would not have thrown if the flags didn't request it
        if ($typeErrorData->hasInvalidChecks()) {
            throw new InvalidTypeError(
                $typeErrorData->getClass(),
                $typeErrorData->getInvalidChecks()
            );
        }
        if ($typeErrorData->hasUnknownProperties()) {
            throw new UnknownPropertiesTypeError(
                $typeErrorData->getClass(),
                $typeErrorData->getUnknownProperties()
            );
        }
        if ($typeErrorData->hasUndefinedProperties()) {
            throw new UndefinedPropertiesTypeError(
                $typeErrorData->getClass(),
                $typeErrorData->getUndefined()
            );
        }

        return $processedValues;
    }

    /**
     * Process property and use casts to return to data ready for serialisation
     *
     * @param mixed $property
     * @param int $flags
     *
     * @return array|mixed
     */
    public function castValueToData($property, int $flags = NONE)
    {
        // Cast single values first so a collection type can be unpacked and
        // then each item be cast toData
        foreach ($this->getTypeCasts() as $type => $cast) {
            $property = $cast->toData($this->name, $property, $flags);
        }

        // Cast each item in array to array casts
        if (is_array($property) && count($property) > 0) {
            foreach ($this->getArrayTypeCasts() as $type => $cast) {
                $property = $this->castArrayItemsToData($cast, $property, $flags);
            }
        }

        return $property;
    }

    /**
     * @param PropertyCast $cast
     * @param mixed $property
     * @param int $flags
     *
     * @return array|mixed
     */
    private function castArrayItemsToData(
        PropertyCast $cast,
        $property,
        int $flags = NONE
    ) {
        $propertyItems = [];

        foreach ($property as $i => $valueItem) {
            $propertyItems[$i] = $cast->toData($this->name, $valueItem, $flags);
        }

        return $propertyItems;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValidValue($value): bool
    {
        // Shortcut for null first
        if ($value === null && $this->isNullable) {
            return true;
        }

        // Spin through single types
        foreach ($this->getTypes() as $currentType) {
            if ($this->valueMatchesType($value, $currentType)) {
                return true;
            }
        }

        // If single types didn't match and the value isn't an array there is
        // nothing left to check
        if (!is_array($value)) {
            return false;
        }

        // Spin through array types
        foreach ($this->getArrayTypes() as $currentType) {
            if ($this->allValuesMatchType($value, $currentType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param string $type
     *
     * @return bool
     */
    private function valueMatchesType($value, string $type): bool
    {
        if ($type === 'mixed') {
            return true;
        }

        if ($value instanceof $type) {
            return true;
        }

        return gettype($value) === (self::TYPE_ALIASES[$type] ?? $type);
    }

    /**
     * @param mixed[] $values
     * @param string $type
     *
     * @return bool
     */
    private function allValuesMatchType(array $values, string $type): bool
    {
        $allItemsMatch = true;

        foreach ($values as $arrayItemValue) {
            if (!$this->valueMatchesType($arrayItemValue, $type)) {
                $allItemsMatch = false;
                break;
            }
        }

        return $allItemsMatch;
    }
}
