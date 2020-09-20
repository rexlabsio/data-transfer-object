<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Rexlabs\DataTransferObject\Exceptions\ImmutableTypeError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;

abstract class DataTransferObject
{
    /** @var null|FactoryContract */
    protected static $factory;

    /** @var int Base flags merge with flag parameters passed to `make` */
    protected $baseFlags = NONE;

    /** @var PropertyType[] Keyed by property name */
    protected $propertyTypes;

    /** @var array Keyed by property name */
    protected $properties;

    /** @var int Behaviour flags */
    protected $flags;

    /**
     * Additional unknown properties provided at make time
     * Usually discarded unless made with the `TRACK_UNKNOWN_PROPERTIES` flag
     *
     * @var array
     */
    private $unknownProperties;

    /**
     * No validation or checking is done in constructor
     * Use `MyTransferObject::make($data)` instead
     *
     * @param array $propertyTypes keyed by property name
     * @param array $properties keyed by property name
     * @param array $unknownProperties
     * @param int $flags
     *
     * @internal Use `MyTransferObject::make`
     */
    public function __construct(
        array $propertyTypes,
        array $properties,
        array $unknownProperties,
        int $flags
    ) {
        $this->propertyTypes = $propertyTypes;
        $this->properties = $properties;
        $this->flags = $flags;
        $this->unknownProperties = $unknownProperties;
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
     * Make a valid dto instance ensuring:
     *
     *  - only "known" properties are used
     *  - defaults are used if requested
     *  - unknown properties are tracked if requested
     *  - throw if unknown properties aren't requested to ignore or track
     *  - create partial if requested
     *  - throw when undefined properties and not partial
     *  - throw if types are invalid
     *  - adapt exceptions thrown from nested properties to show full path
     *
     * @param array $parameters
     * @param int $flags
     * @return static
     */
    public static function make(array $parameters, int $flags = NONE): self
    {
        $factory = self::getFactory();
        $meta = $factory->getClassMetadata(static::class);
        $propertyTypes = $meta->propertyTypes;
        $flags = $meta->baseFlags | $flags;

        // Sort properties by known and unknown
        $properties = [];
        $invalidChecks = [];
        $unknownProperties = [];
        $undefined = [];
        foreach ($parameters as $name => $value) {
            $propertyType = $propertyTypes[$name] ?? null;

            if ($propertyType === null) {
                $unknownProperties[$name] = $value;
                continue;
            }

            // Errors from casts to nested properties can make debugging difficult
            // Adapt exceptions from nested properties to show nested paths
            // eg class UserData has property "parent" that can be another UserData
            // An invalid "first_name" on the nested parent will show as "parent.first_name".
            // Exceptions can bubble up multiple levels eg "parent.parent.parent.first_name"
            try {
                $value = $factory->processValue($propertyType, $value, $flags);
            } catch (InvalidTypeError $e) {
                foreach ($e->getNestedTypeChecks($name) as $nestedCheck) {
                    $invalidChecks[] = $nestedCheck;
                }
                // Proceed to type check for more context in exception
            } catch (UnknownPropertiesTypeError $e) {
                foreach ($e->getNestedPropertyNames($name) as $nestedPropertyName) {
                    // Safe to use null and ignore value since exception will
                    // only throw when unknown properties are not being tracked
                    $unknownProperties[$nestedPropertyName] = null;
                }

                // Skip type check so unknown properties exception will be thrown first
                continue;
            } catch (UndefinedPropertiesTypeError $e) {
                foreach ($e->getNestedPropertyNames($name) as $nestedPropertyName) {
                    $undefined[] = $nestedPropertyName;
                }

                // Skip type check so undefined properties exception will be thrown first
                continue;
            }

            $check = $propertyType->checkValue($value);
            if (!$check->isValid()) {
                $invalidChecks[] = $check;
                continue;
            }

            $properties[$name] = $value;
        }

        if (!empty($invalidChecks)) {
            throw new InvalidTypeError(static::class, $invalidChecks);
        }

        // Set defaults for uninitialised properties when explicitly requested
        if ($flags & DEFAULTS) {
            foreach ($propertyTypes as $propertyType) {
                // Defaults ignore properties already defined
                if (array_key_exists($propertyType->getName(), $properties)) {
                    continue;
                }

                // No default available to use
                if (!$propertyType->hasValidDefault()) {
                    continue;
                }

                // Set the undefined property to the default
                $properties[$propertyType->getName()] = $propertyType->getDefault();
            }
        }

        // Track unknown properties if requested
        $trackedUnknownProperties = ($flags & TRACK_UNKNOWN_PROPERTIES)
            ? $unknownProperties
            : [];

        // Throw unknown properties unless requested to track or ignore
        if (!($flags & (IGNORE_UNKNOWN_PROPERTIES | TRACK_UNKNOWN_PROPERTIES)) && count($unknownProperties) > 0) {
            throw new UnknownPropertiesTypeError(static::class, array_keys($unknownProperties));
        }

        $dto = new static($propertyTypes, $properties, $trackedUnknownProperties, $flags);

        // Return before check for uninitialised properties for partial
        if ($flags & PARTIAL) {
            return $dto;
        }

        // Throw uninitialised properties
        $undefined = array_merge($undefined, $dto->getUndefinedPropertyNames());
        if (count($undefined) > 0) {
            throw new UndefinedPropertiesTypeError(static::class, $undefined);
        }

        return $dto;
    }

    /**
     * @param array $override
     * @param null|int $flags Use current instance flags on null, else use provided flags
     * @return static
     */
    public function remake(array $override, $flags = null): self
    {
        return self::make(
            array_merge($this->getDefinedProperties(), $override),
            $flags ?? $this->flags
        );
    }

    /**
     * @param array $onlyPropertyNames
     * @param array $override
     * @param null|int $flags Use current instance flags on null, else use provided flags
     *
     * @return static
     */
    public function remakeOnly(
        array $onlyPropertyNames,
        array $override,
        $flags = null
    ): self {
        $this->assertKnownPropertyNames($onlyPropertyNames);

        $properties = array_intersect_key(
            $this->getDefinedProperties(),
            array_flip($onlyPropertyNames)
        );

        return self::make(
            array_merge($properties, $override),
            $flags ?? $this->flags
        );
    }

    /**
     * @param array $exceptPropertyNames
     * @param array $override
     * @param null|int $flags Use current instance flags on null, else use provided flags
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
            self::$factory = new Factory([]);
        }

        return self::$factory;
    }

    /**
     * Override the default property factory used when DTOs are instantiated
     *
     * @param null|FactoryContract $factory
     *
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
     *
     * @return PropertyType
     */
    private function getPropertyType(string $name): PropertyType
    {
        if (!array_key_exists($name, $this->propertyTypes)) {
            throw new UnknownPropertiesTypeError(static::class, [$name]);
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
        if (!array_key_exists($name, $this->propertyTypes)) {
            throw new UnknownPropertiesTypeError(static::class, [$name]);
        }

        if (!array_key_exists($name, $this->properties)) {
            throw new UndefinedPropertiesTypeError(static::class, [$name]);
        }

        return $this->properties[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $propertyType = $this->propertyTypes[$name] ?? null;
        if ($propertyType === null) {
            throw new UnknownPropertiesTypeError(static::class, [$name]);
        }

        $propertyType = $this->getPropertyType($name);

        if (!$this->isMutable()) {
            throw new ImmutableTypeError(static::class, $propertyType->getName());
        }

        $processedValue = self::getFactory()->processValue($propertyType, $value, $this->flags);

        $check = $propertyType->checkValue($processedValue);
        if (!$check->isValid()) {
            throw new InvalidTypeError(static::class, [$check]);
        }

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
            if (!array_key_exists($name, $this->propertyTypes)) {
                throw new UnknownPropertiesTypeError(static::class, [$name]);
            }

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
     * @return bool
     */
    public function isMutable(): bool
    {
        return (bool) ($this->flags & MUTABLE);
    }

    /**
     * @param string $name
     * @return bool
     */
    private function hasNestedDto(string $name): bool
    {
        // Check property name is valid
        $this->getPropertyType($name);

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
        // Get defaults for undefined properties
        $defaults = [];
        foreach ($this->getUndefinedPropertyNames() as $name) {
            $propertyType = $this->propertyTypes[$name];
            if ($propertyType->hasValidDefault()) {
                $defaults[$name] = $propertyType->getDefault();
            }
        }

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
     * @return array
     */
    public function getUnknownPropertyNames(): array
    {
        return array_keys($this->unknownProperties);
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
            throw new UndefinedPropertiesTypeError(static::class, $undefined);
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
            throw new UnknownPropertiesTypeError(static::class, $unknown);
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

    /**
     * @return array
     */
    public function getUnknownProperties(): array
    {
        return $this->unknownProperties;
    }
}
