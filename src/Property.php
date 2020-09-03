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

        if (!$this->isValidType($value, $flags)) {
            throw new InvalidTypeError($this->name, $this->getTypes($flags), $value);
        }

        return $value;
    }

    /**
     * Return an array with default value keyed by name or empty array if no
     * default can be made
     *
     * @param int $flags
     * @return array
     */
    public function mapProcessedDefault(int $flags): array
    {
        $defaults = [];

        // Nullable first
        if ($this->canDefaultToNull($flags)) {
            $defaults[$this->name] = null;
        }

        // False next
        if ($this->canDefaultToFalse($flags)) {
            $defaults[$this->name] = false;
        }

        // Empty array next
        if ($this->canDefaultToArray($flags)) {
            $defaults[$this->name] = [];
        }

        // Property default last
        if ($this->hasDefault) {
            $defaults[$this->name] = $this->default;
        }

        return $defaults;
    }

    /**
     * @param int $flags
     * @return bool
     */
    private function canDefaultToNull(int $flags): bool
    {
        if (!$this->isNullable($flags)) {
            return false;
        }

        return (bool) ($flags & NULLABLE_DEFAULT_TO_NULL);
    }

    /**
     * @param int $flags
     * @return bool
     */
    private function canDefaultToFalse(int $flags): bool
    {
        if (!$this->isBool) {
            return false;
        }

        return (bool) ($flags & BOOL_DEFAULT_TO_FALSE);
    }

    /**
     * @param int $flags
     * @return bool
     */
    private function canDefaultToArray(int $flags): bool
    {
        if (!$this->isArray) {
            return false;
        }

        return (bool) ($flags & ARRAY_DEFAULT_TO_EMPTY_ARRAY);
    }

    /**
     * Will always have a default of null, use mapProcessedDefault to determine
     * if a default exists or not
     *
     * @param int $flags
     * @return mixed
     */
    public function processDefault(int $flags)
    {
        return $this->mapProcessedDefault($flags)[$this->name] ?? null;
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
     * @param int $flags
     * @return bool
     */
    public function isValidType($value, int $flags): bool
    {
        if ($value === null && $this->isNullable($flags)) {
            return true;
        }

        foreach ($this->getTypes($flags) as $currentType) {
            $isValidType = $this->assertTypeEquals($currentType, $value);

            if ($isValidType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $flags
     * @return bool
     */
    protected function isNullable(int $flags): bool
    {
        if ($flags & NULLABLE) {
            return true;
        }

        if ($flags & NOT_NULLABLE) {
            return false;
        }

        return $this->isNullable;
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
     * @param int $flags
     * @return array
     */
    public function getTypes(int $flags): array
    {
        $hasNullableType = in_array('null', $this->types, true);

        // Strip nullable type
        if (($flags & NOT_NULLABLE) && $hasNullableType) {
            return array_filter($this->types, function (string $type): bool {
                return $type !== 'null';
            });
        }

        // Add nullable type
        if (($flags & NULLABLE) && !$hasNullableType) {
            return array_merge($this->types, ['null']);
        }

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
