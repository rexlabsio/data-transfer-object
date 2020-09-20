<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;

use function in_array;

class Property
{
    public const MAPPED_TYPES = [
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'double',
    ];

    /** @var FactoryContract */
    private $factory;

    /** @var string */
    private $name;

    /** @var array */
    private $types;

    /** @var array */
    private $arrayTypes;

    /** @var bool */
    private $isNullable;

    /** @var bool */
    private $isBool;

    /** @var bool */
    private $isArray;

    /** @var bool */
    private $hasDefault;

    /** @var mixed */
    private $default;

    /**
     * Property constructor.
     *
     * @param FactoryContract $factory
     * @param string $name
     * @param array $types
     * @param array $arrayTypes
     * @param bool $hasDefault
     * @param mixed $default
     */
    public function __construct(
        FactoryContract $factory,
        string $name,
        array $types,
        array $arrayTypes,
        bool $hasDefault,
        $default
    ) {
        $this->factory = $factory;
        $this->name = $name;
        $this->types = $types;
        $this->arrayTypes = $arrayTypes;
        $this->isNullable = in_array('null', $types, true);
        $this->isArray = !empty($arrayTypes) || in_array('array', $types, true);
        $this->isBool = in_array('bool', $types, true);
        $this->hasDefault = $hasDefault;
        $this->default = $default;
    }

    /**
     * @param mixed $value
     * @param int $flags
     * @return mixed
     */
    public function processValue($value, int $flags)
    {
        if (!($flags & MUTABLE)) {
            throw new ImmutableError($this->name);
        }

        if (is_array($value)) {
            $value = $this->shouldBeCastToCollection($value)
                ? $this->castCollection($value, $flags)
                : $this->cast($value, $flags);
        }

        if (!$this->isValidType($value)) {
            throw new InvalidTypeError($this->name, $this->getTypes(), $value);
        }

        return $value;
    }

    /**
     * Return an array with default value keyed by name or empty array if no
     * default can be made
     *
     * @return array
     */
    public function mapProcessedDefault(): array
    {
        $defaults = [];

        // Order of default cascading is important
        // Lower checks will override higher ones
        // Generally preference is for the "least meaningful" value to win
        // eg null will override false or empty array

        if ($this->isArray) {
            $defaults[$this->name] = [];
        }

        if ($this->isBool) {
            $defaults[$this->name] = false;
        }

        if ($this->isNullable) {
            $defaults[$this->name] = null;
        }

        // Property default last
        if ($this->hasDefault) {
            $defaults[$this->name] = $this->default;
        }

        return $defaults;
    }

    /**
     * @param array $values
     * @return bool
     */
    protected function shouldBeCastToCollection(array $values): bool
    {
        if (empty($values)) {
            return false;
        }

        foreach ($values as $key => $value) {
            // Only look for numeric keys
            if (is_string($key)) {
                return false;
            }

            // Looking for collection of complex types
            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $values
     * @param int $flags
     * @return array
     */
    protected function castCollection(array $values, int $flags): array
    {
        /**
         * @var string|null $castTo
         */
        $castTo = null;

        foreach ($this->arrayTypes as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        if ($castTo === null) {
            return $values;
        }

        $casts = [];

        foreach ($values as $value) {
            $casts[] = $this->factory->make($castTo, $value, $flags);
        }

        return $casts;
    }

    /**
     * @param mixed $value
     * @param int $flags
     * @return mixed|DataTransferObject
     */
    protected function cast($value, int $flags)
    {
        /**
         * @var string|null $castTo
         */
        $castTo = null;

        foreach ($this->types as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        if ($castTo === null) {
            return $value;
        }

        return $this->factory->make($castTo, $value, $flags);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValidType($value): bool
    {
        if ($value === null && $this->isNullable) {
            return true;
        }

        foreach ($this->getTypes() as $currentType) {
            $isValidType = $this->assertTypeEquals($currentType, $value);

            if ($isValidType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool
     */
    protected function assertTypeEquals(string $type, $value): bool
    {
        if (strpos($type, '[]') !== false) {
            return $this->isValidGenericCollection($type, $value);
        }

        if ($type === 'mixed' && $value !== null) {
            return true;
        }

        return $value instanceof $type
            || gettype($value) === (self::MAPPED_TYPES[$type] ?? $type);
    }

    /**
     * @param string $type
     * @param mixed $collection
     * @return bool
     */
    protected function isValidGenericCollection(string $type, $collection): bool
    {
        if (!is_array($collection)) {
            return false;
        }

        $valueType = str_replace('[]', '', $type);

        foreach ($collection as $value) {
            if (!$this->assertTypeEquals($valueType, $value)) {
                return false;
            }
        }

        return true;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
