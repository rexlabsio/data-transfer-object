<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;

class DataTransferObject
{
    /** @var null|FactoryContract */
    protected static $factory;

    /** @var int Override to set default behaviour flags */
    protected $defaultFlags = NULLABLE_DEFAULT_TO_NULL
       | ARRAY_DEFAULT_TO_EMPTY_ARRAY;

    /** @var Property[] Keyed by property name */
    protected $propertyTypes;

    /** @var array Keyed by property name */
    protected $properties;

    /** @var int Behaviour flags */
    protected $flags;

    /**
     * No validation or checking is done in constructor
     * Use `MyTransferObject::make($data)` instead
     *
     * @internal Use `MyTransferObject::make`
     *
     * @param array $propertyTypes keyed by property name
     * @param array $properties keyed by property name
     * @param int $flags
     */
    public function __construct(
        array $propertyTypes,
        array $properties,
        int $flags
    ) {
        $this->propertyTypes = $propertyTypes;
        $this->properties = $properties;
        $this->flags = $flags;
    }

    /**
     * Override to define default property values
     *
     * @return array
     */
    protected static function getDefaults(): array
    {
        return [];
    }

    /**
     * @param array $parameters
     * @param int $flags
     * @return static
     */
    public static function make(array $parameters, int $flags = NONE): self
    {
        return self::getFactory()->make(static::class, $parameters, $flags);
    }

    /**
     * @param array $override
     * @param int $flags
     * @return static
     */
    public function remake(array $override, int $flags = NONE): self
    {
        return self::make(
            array_merge($this->getDefinedProperties(), $override),
            $flags
        );
    }

    /**
     * @param array $onlyPropertyNames
     * @param array $override
     * @param int $flags
     *
     * @return static
     */
    public function remakeOnly(
        array $onlyPropertyNames,
        array $override,
        int $flags = NONE
    ): self {
        $this->assertKnownPropertyNames($onlyPropertyNames);

        $properties = array_intersect_key(
            $this->getDefinedProperties(),
            array_flip($onlyPropertyNames)
        );

        return self::make(array_merge($properties, $override), $flags);
    }

    /**
     * @param array $exceptPropertyNames
     * @param array $override
     * @param int $flags
     *
     * @return static
     */
    public function remakeExcept(
        array $exceptPropertyNames,
        array $override,
        int $flags = NONE
    ): self {
        $this->assertKnownPropertyNames($exceptPropertyNames);

        $properties = array_diff_key(
            $this->getDefinedProperties(),
            array_flip($exceptPropertyNames)
        );

        return self::make(array_merge($properties, $override), $flags);
    }

    /**
     * Get the shared property factory. Caches class property data so each DTOs
     * docs are only parsed once
     *
     * @return FactoryContract
     */
    public static function getFactory(): FactoryContract
    {
        if (self::$factory === null) {
            self::setFactory(new Factory([]));
        }

        return self::$factory;
    }

    /**
     * Override the default property factory used when DTOs are instantiated
     *
     * @param null|FactoryContract $factory
     * @return void
     */
    public static function setFactory(?FactoryContract $factory): void
    {
        self::$factory = $factory;
    }

    /**
     * Get named type or throw type error if missing
     *
     * @param string $name
     * @return Property
     */
    private function type(string $name): Property
    {
        if (!array_key_exists($name, $this->propertyTypes)) {
            throw new UnknownPropertiesError([$name]);
        }

        return $this->propertyTypes[$name];
    }

    /**
     * Will return the default value even on a partial where the data has not
     * been set.
     *
     * @param string $name
     * @return mixed Null if uninitialised
     */
    public function __get(string $name)
    {
        $type = $this->type($name);

        return array_key_exists($name, $this->properties)
            ? $this->properties[$name]
            : $type->processDefault($this->flags);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $processedValue = $this
            ->type($name)
            ->processValue($value, $this->flags);

        $this->properties[$name] = $processedValue;
    }

    /**
     * Php isset documentation:
     *
     *  "Determine if a variable is considered set, this means if a variable is
     *   declared and is different than NULL."
     *
     * https://www.php.net/manual/en/function.isset.php
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * Has the property been assigned a value (including null)
     * Supports dot '.' notation for nested DTOs
     *
     * @param string $name
     * @return bool
     */
    public function isDefined(string $name): bool
    {
        if (array_key_exists($name, $this->properties)) {
            return true;
        }

        // Check for dot '.' notation
        $dotPos = strpos($name, '.');

        if ($dotPos === false) {
            // Ensure property name is even valid
            $this->type($name);

            return false;
        }

        [$start, $remainder] = explode('.', $name, 2);

        // Does string before dot map to a known property
        if (!$this->hasNestedDto($start)) {
            return false;
        }

        /**
         * @var DataTransferObject $nestedDto
         */
        $nestedDto = $this->properties[$start];

        return $nestedDto->isDefined($remainder);
    }

    /**
     * @param string $name
     * @return bool
     */
    private function hasNestedDto(string $name): bool
    {
        // Check property name is valid
        $this->type($name);

        $value = $this->properties[$name] ?? null;

        return $value instanceof self;
    }

    /**
     * @deprecated Use the more explicit `getDefinedProperties` or `getPropertiesWithDefaults`
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->getDefinedProperties();
    }

    /**
     * @return array
     */
    public function getDefinedProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getPropertiesWithDefaults(): array
    {
        // Set missing properties to defaults
        $defaults = array_reduce(
            array_diff_key($this->propertyTypes, $this->getDefinedPropertyNames()),
            function (array $carry, Property $type): array {
                foreach ($type->mapProcessedDefault($this->flags) as $name => $default) {
                    $carry[$name] = $default;
                }
                return $carry;
            },
            []
        );

        // Safe to merge because only missing keys were used to load defaults
        return array_merge($defaults, $this->properties);
    }

    /**
     * @return array
     */
    public function getUndefinedPropertyNames(): array
    {
        return array_values(
            array_diff(
                array_keys($this->propertyTypes),
                $this->getDefinedPropertyNames()
            )
        );
    }

    /**
     * @return array
     */
    public function getDefinedPropertyNames(): array
    {
        return array_keys($this->properties);
    }

    /**
     * @param string|array $propertyNames
     *
     * @return void
     */
    public function assertDefined($propertyNames): void
    {
        $this->assertKnownPropertyNames($propertyNames);

        $undefined = array_filter((array) $propertyNames, function (string $propertyName) {
            return !$this->isDefined($propertyName);
        });

        if (!empty($undefined)) {
            throw new UninitialisedPropertiesError($undefined, static::class);
        }
    }

    /**
     * @param string|array $propertyNames
     *
     * @return void
     */
    private function assertKnownPropertyNames($propertyNames): void
    {
        $unknown = array_diff((array)$propertyNames, array_keys($this->propertyTypes));

        if (!empty($unknown)) {
            throw new UnknownPropertiesError($unknown);
        }
    }

    /**
     * @param int $options
     * @param int $depth
     * @return string
     */
    public function toJson($options = 0, $depth = 512): string
    {
        return json_encode($this->toArray(), $options, $depth);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->recursiveToArray($this->properties);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function recursiveToArray(array $data): array
    {
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $data[$name] = $this->recursiveToArray($value);
            } elseif (method_exists($value, 'toArray')) {
                $data[$name] = $value->toArray();
            }
        }

        return $data;
    }
}
