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
    private $hasDefault;

    /** @var mixed */
    private $default;

    /**
     * Property constructor.
     *
     * @param string $name
     * @param array $types
     * @param bool $hasDefault
     * @param mixed $default
     */
    public function __construct(
        string $name,
        array $types,
        bool $hasDefault,
        $default
    ) {
        $this->types = [];
        $this->arrayTypes = [];
        $this->isNullable = false;
        $this->isArray = false;
        $this->isBool = false;

        foreach ($types as $type) {
            if ($type === 'null') {
                $this->isNullable = true;
            }

            if ($type === 'bool' || $type === self::TYPE_ALIASES['bool']) {
                $this->isBool = true;
            }

            if ($type === 'array') {
                $this->isArray = true;
            }

            if (substr($type, -2) === '[]') {
                $this->arrayTypes[] = substr($type, 0, -2);
                $this->isArray = true;
            } else {
                $this->types[] = $type;
            }
        }

        $this->name = $name;
        $this->hasDefault = $hasDefault;
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
    public function hasDefault(): bool
    {
        return $this->hasDefault;
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
     * @return bool
     */
    public function isValidType($value): bool
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
