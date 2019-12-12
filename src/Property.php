<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function class_exists;

class Property
{
    /** @var array */
    protected static $typeMapping = [
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'double',
    ];

    /** @var bool */
    protected $isNullable = false;

    /** @var array */
    protected $types = [];

    /** @var array */
    protected $arrayTypes = [];

    /** @var bool */
    private $hasDefault;

    /** @var mixed */
    private $default;

    public function __construct(
        string $namespace,
        Collection $useStatements,
        string $docType,
        bool $hasDefault,
        $default
    ) {
        $this->resolveTypeDefinition($namespace, $useStatements, $docType);
        $this->hasDefault = $hasDefault;
        $this->default = $default;

        if (!$this->hasDefault && !$this->isNullable) {
            $this->setDefaultDefault();
        }
    }

    /**
     * Check types to pick an appropriate default value
     *
     * @return void
     */
    private function setDefaultDefault(): void
    {
        // Default to empty array
        if (!empty($this->arrayTypes)) {
            $this->hasDefault = true;
            $this->default = [];
            return;
        }

        // Default to false
        if ($this->types === ['bool']) {
            $this->hasDefault = true;
            $this->default = false;
            return;
        }

        // Default to empty string
        if ($this->types === ['string']) {
            $this->hasDefault = true;
            $this->default = '';
            return;
        }
    }

    public function processValue(string $name, $value)
    {
        if (is_array($value)) {
            $value = $this->shouldBeCastToCollection($value) ? $this->castCollection($value) : $this->cast($value);
        }

        if (! $this->isValidType($value)) {
            throw DataTransferObjectError::invalidType($name, $this, $value);
        }

        return $value;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

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
     * @param string|null $namespace
     * @param Collection|string[] $useStatements
     * @param string $docType
     * @return void
     */
    protected function resolveTypeDefinition(
        ?string $namespace,
        Collection $useStatements,
        string $docType
    ): void {
        $this->types = $this->mapTypes($namespace, $useStatements, explode('|', $docType));
        $this->arrayTypes = str_replace(
            '[]',
            '',
            array_filter($this->types, function (string $type) {
                return Str::endsWith($type, '[]') || $type === 'array';
            })
        );

        $this->isNullable = strpos($docType, 'null') !== false;
    }

    /**
     * @param null|string $namespace
     * @param Collection $useStatements
     * @param array $types
     * @return array
     */
    private function mapTypes(
        ?string $namespace,
        Collection $useStatements,
        array $types
    ): array {
        return collect($types)
            ->map(function (string $type) use ($namespace, $useStatements): string {
                // Found class or alias in use statement
                if ($useStatements->has($type)) {
                    return $useStatements->get($type);
                }

                // Found a class in this namespace
                $thisNamespaceClass = sprintf('%s\\%s', $namespace, $type);
                if (class_exists($thisNamespaceClass)) {
                    return $thisNamespaceClass;
                }

                // Attempt basic class name or primitive type
                return $type;
            })
            ->all();
    }

    protected function isValidType($value): bool
    {
        if ($this->isNullable && $value === null) {
            return true;
        }

        foreach ($this->types as $currentType) {
            $isValidType = $this->assertTypeEquals($currentType, $value);

            if ($isValidType) {
                return true;
            }
        }

        return false;
    }

    protected function cast($value)
    {
        $castTo = null;

        foreach ($this->types as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        if (! $castTo) {
            return $value;
        }

        return new $castTo($value);
    }

    protected function castCollection(array $values): array
    {
        $castTo = null;

        foreach ($this->arrayTypes as $type) {
            if (! is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        if (! $castTo) {
            return $values;
        }

        $casts = [];

        foreach ($values as $value) {
            $casts[] = new $castTo($value);
        }

        return $casts;
    }

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
            if (! is_array($value)) {
                return false;
            }
        }

        return true;
    }

    protected function assertTypeEquals(string $type, $value): bool
    {
        if (strpos($type, '[]') !== false) {
            return $this->isValidGenericCollection($type, $value);
        }

        if ($type === 'mixed' && $value !== null) {
            return true;
        }

        return $value instanceof $type
            || gettype($value) === (self::$typeMapping[$type] ?? $type);
    }

    protected function isValidGenericCollection(string $type, $collection): bool
    {
        if (! is_array($collection)) {
            return false;
        }

        $valueType = str_replace('[]', '', $type);

        foreach ($collection as $value) {
            if (! $this->assertTypeEquals($valueType, $value)) {
                return false;
            }
        }

        return true;
    }
}
