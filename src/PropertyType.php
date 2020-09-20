<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

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

    /** @var bool */
    private $isNullable;

    /** @var bool */
    private $isBool;

    /** @var bool */
    private $isArray;

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
     * @param bool $isNullable
     * @param bool $isBool
     * @param bool $isArray
     * @param bool $hasValidDefault
     * @param mixed $default
     */
    public function __construct(
        string $name,
        array $types,
        array $arrayTypes,
        bool $isNullable,
        bool $isBool,
        bool $isArray,
        bool $hasValidDefault,
        $default
    ) {
        $this->name = $name;
        $this->types = $types;
        $this->arrayTypes = $arrayTypes;
        $this->isNullable = $isNullable;
        $this->isBool = $isBool;
        $this->isArray = $isArray;
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
     * @return array
     */
    public function getAllTypes(): array
    {
        return array_merge($this->types, array_map(function ($arrayType) {
            return $arrayType . '[]';
        }, $this->arrayTypes));
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
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return bool
     */
    public function isBool(): bool
    {
        return $this->isBool;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
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
            $this->isValidValueForType($value)
        );
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValidValueForType($value): bool
    {
        if ($value === null && $this->isNullable()) {
            return true;
        }

        foreach ($this->getTypes() as $currentType) {
            if ($currentType === 'mixed') {
                return true;
            }

            $isValidType = $this->assertTypeEquals($currentType, $value);

            if ($isValidType) {
                return true;
            }
        }

        if (is_array($value)) {
            foreach ($this->getArrayTypes() as $currentType) {
                if ($currentType === 'mixed') {
                    return true;
                }

                $isValidType = true;
                foreach ($value as $arrayItemValue) {
                    $isValidType = $isValidType && $this->assertTypeEquals($currentType, $arrayItemValue);
                }

                if ($isValidType) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool
     */
    private function assertTypeEquals(string $type, $value): bool
    {
        return $value instanceof $type
            || gettype($value) === (self::TYPE_ALIASES[$type] ?? $type);
    }
}
