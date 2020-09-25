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
    public function processValueToCast($value, int $flags)
    {
        // Implicit scalar patching - numeric strings can safely cast to int
        if ($this->isInt && !$this->isString && is_numeric($value)) {
            $value = (int)$value;
        }

        // Check each single value type for possible casts
        foreach ($this->getTypeCasts() as $type => $cast) {
            if (!$cast->shouldCastValue($value)) {
                continue;
            }

            // The first cast that can handle the value does the cast
            return $cast->castToType($this->name, $value, $type, $flags);
        }

        // If there aren't indexed items then no array cast can be done
        if (empty($value) || !$this->isIndexedArray($value)) {
            return $value;
        }

        // Assuming that each item of the collection is the same type
        // It's not worth the complexity of trying to support mixed collections
        $first = reset($value);
        foreach ($this->getArrayTypeCasts() as $type => $cast) {
            if (!$cast->shouldCastValue($first)) {
                continue;
            }

            $processedValues = [];
            $invalidChecks = [];
            $unknownProperties = [];
            $undefined = [];
            $class = 'Unknown';

            // Use the cast on each item in the collection
            // Collect nested exception data to rethrow at the end
            foreach ($value as $i => $valueItem) {
                // Catch and adapt exceptions to show nested array index
                // eg user.children.0.first_name
                try {
                    $processedValues[] = $cast->castToType($this->name, $valueItem, $type, $flags);
                } catch (InvalidTypeError $e) {
                    $class = $e->getClass();
                    foreach ($e->getNestedTypeChecks((string)$i) as $nestedCheck) {
                        $invalidChecks[] = $nestedCheck;
                    }
                } catch (UnknownPropertiesTypeError $e) {
                    $class = $e->getClass();
                    foreach ($e->getNestedPropertyNames((string)$i) as $nestedPropertyName) {
                        // Safe to use null and ignore value since exception will
                        // only throw when unknown properties are not being tracked
                        $unknownProperties[$nestedPropertyName] = null;
                    }
                } catch (UndefinedPropertiesTypeError $e) {
                    $class = $e->getClass();
                    foreach ($e->getNestedPropertyNames((string)$i) as $nestedPropertyName) {
                        $undefined[] = $nestedPropertyName;
                    }
                }
            }

            // No need to recheck flags since these nested property exceptions
            // would not have thrown if the flags didn't request it
            if (!empty($invalidChecks)) {
                throw new InvalidTypeError($class, $invalidChecks);
            }
            if (!empty($unknownProperties)) {
                throw new UnknownPropertiesTypeError($class, $unknownProperties);
            }
            if (!empty($undefined)) {
                throw new UndefinedPropertiesTypeError($class, $undefined);
            }

            return $processedValues;
        }

        return $value;
    }

    /**
     * Process property and use casts to return to data ready for serialisation
     *
     * @param mixed $property
     * @param int $flags
     *
     * @return array|mixed
     */
    public function processValueToData($property, int $flags = NONE)
    {
        // Check each cast for single values
        foreach ($this->getTypeCasts() as $type => $cast) {
            if (!$cast->shouldMapToData($property)) {
                continue;
            }

            // Use the first cast that can map the property to data
            return $cast->toData($this->name, $property, $flags);
        }

        // If the value isn't a collection there is nothing left to do
        if (!$this->isIndexedArray($property)) {
            return $property;
        }

        // Assuming that each item of the collection is the same type
        // It's not worth the complexity of trying to support mixed collections
        $first = reset($property);
        foreach ($this->getArrayTypeCasts() as $type => $cast) {
            if (!$cast->shouldMapToData($first)) {
                continue;
            }

            $data = [];
            // Use the cast on each item in the collection
            foreach ($property as $i => $valueItem) {
                $data[] = $cast->toData($this->name, $valueItem, $flags);
            }

            return $data;
        }

        return $property;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function isIndexedArray($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Fail if any keys are strings
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                return false;
            }
        }

        return true;
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
