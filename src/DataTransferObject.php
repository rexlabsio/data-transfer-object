<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use Rexlabs\DataTransferObject\Exceptions\ImmutableTypeError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnexpectedlyDefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Factory\Factory;
use Rexlabs\DataTransferObject\Factory\FactoryContract;
use Rexlabs\DataTransferObject\Type\IsDefinedReference;
use Rexlabs\DataTransferObject\Type\PropertyReference;
use Rexlabs\DataTransferObject\Type\PropertyType;

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

    /** @var IsDefinedReference|static */
    private $refIsDefined;

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
        $this->refIsDefined = new IsDefinedReference($this);
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
     * Override to define casts for property types
     *
     * @return array
     */
    protected static function getCasts(): array
    {
        return [];
    }

    /**
     * Get a property reference for code completion / refactoring on property names
     * References will return their string name
     *
     * @return PropertyReference|static Magic mixin for property name code completion / refactoring
     */
    public static function ref(): PropertyReference
    {
        return self::getFactory()->getClassMetadata(static::class)->ref;
    }

    /**
     * Get a reference for defined properties
     * References will return bool isDefined
     *
     * @return IsDefinedReference|static Magic mixin for property name code completion / refactoring
     */
    public function refIsDefined(): IsDefinedReference
    {
        return $this->refIsDefined;
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
     *
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
                $value = $propertyType->castValueToType($value, $flags);
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
        if ($flags & WITH_DEFAULTS) {
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
     * Make a valid dto instance from json data
     *
     * @param string $json
     * @param int $flags
     * @param int $depth
     * @param int $options
     *
     * @return static
     */
    public static function makeFromJson(
        string $json,
        int $flags = NONE,
        $depth = 512,
        $options = 0
    ): self {
        $data = json_decode($json, true, $depth, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Valid json required: ' . json_last_error_msg());
        }

        return self::make($data, $flags);
    }

    /**
     * @param array $override
     * @param null|int $flags Use current instance flags on null, else use provided flags
     *
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
            self::$factory = Factory::makeDefaultFactory();
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
     * Will return the default value even on a partial where the data has not
     * been set.
     *
     * @param string $name
     *
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
     *
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $propertyType = $this->propertyTypes[$name] ?? null;
        if ($propertyType === null) {
            throw new UnknownPropertiesTypeError(static::class, [$name]);
        }

        if (!$this->isMutable()) {
            throw new ImmutableTypeError(static::class, $propertyType->getName());
        }

        $processedValue = $propertyType->castValueToType($value, $this->flags);

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
     *
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
     *
     * @return bool
     */
    public function isDefined(string $name): bool
    {
        if (array_key_exists($name, $this->properties)) {
            return true;
        }

        $sections = explode('.', $name);
        $current = $this;
        $defined = true;
        $path = [];

        while ($section = array_shift($sections)) {
            $path[] = $section;
            if ($current instanceof self) {
                if (!array_key_exists($section, $current->propertyTypes)) {
                    throw new UnknownPropertiesTypeError(static::class, [implode('.', $path)]);
                }
                if (array_key_exists($section, $current->properties)) {
                    $current = $current->__get($section);
                    continue;
                }
            } elseif ($current instanceof ArrayAccess) {
                if ($current->offsetExists($section)) {
                    $current = $current[$section];
                    continue;
                }
            } elseif (is_array($current)) {
                if (array_key_exists($section, $current)) {
                    $current = $current[$section];
                    continue;
                }
            } elseif (is_object($current)) {
                if (property_exists($current, $section)) {
                    $current = $current->{$section};
                    continue;
                }
            }

            $defined = false;
            break;
        }

        return $defined;
    }

    /**
     * Inverse behaviour of isDefined
     *
     * @param string $name
     *
     * @return bool
     */
    public function isUndefined(string $name): bool
    {
        return !$this->isDefined($name);
    }

    /**
     * @return bool
     */
    public function isMutable(): bool
    {
        return (bool)($this->flags & MUTABLE);
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

        $undefined = array_filter(
            (array)$propertyNames,
            function (string $propertyName) {
                return $this->isUndefined($propertyName);
            }
        );

        if (!empty($undefined)) {
            throw new UndefinedPropertiesTypeError(static::class, $undefined);
        }
    }

    /**
     * @param string|array $propertyNames
     *
     * @return void
     */
    public function assertUndefined($propertyNames): void
    {
        $this->assertKnownPropertyNames($propertyNames);

        $defined = array_filter(
            (array)$propertyNames,
            function (string $propertyName) {
                return $this->isDefined($propertyName);
            }
        );

        if (!empty($defined)) {
            throw new UnexpectedlyDefinedPropertiesTypeError(static::class, $defined);
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
     *
     * @return string
     */
    public function toJson($options = 0, $depth = 512): string
    {
        return $this->jsonEncode($this->toArray(), $options, $depth);
    }

    /**
     * @param int $options
     * @param int $depth
     *
     * @return string
     */
    public function toJsonWithDefaults($options = 0, $depth = 512): string
    {
        return $this->jsonEncode($this->toArrayWithDefaults(), $options, $depth);
    }

    /**
     * @param array $data
     * @param int $options
     * @param int $depth
     *
     * @return string
     */
    private function jsonEncode(array $data, $options = 0, $depth = 512): string
    {
        $json = json_encode($data, $options, $depth);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // In the context of the DTO, not being able to serialise means that
            // one or more of the typed properties cannot be serialised.
            //
            // This means that the error is with the class definition not the
            // instance data. So a logic exception is thrown to indicate that
            // the code should be changed.
            //
            // Either the class should only contain properties that can be
            // serialised. Or the class should override and deprecate `toJson`
            // to discourage its use.
            throw new LogicException(
                sprintf(
                    'Properties for %s cannot be serialised: %s',
                    static::class,
                    json_last_error_msg()
                )
            );
        }

        return $json;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->recursiveToArray($this->toData(false), false);
    }

    /**
     * @return array
     */
    public function toArrayWithDefaults(): array
    {
        return $this->recursiveToArray($this->toData(true), true);
    }

    /**
     * @param bool $withDefaults
     *
     * @return array
     */
    private function toData(bool $withDefaults): array
    {
        $properties = $withDefaults
            ? $this->getPropertiesWithDefaults()
            : $this->getDefinedProperties();

        $flags = $withDefaults
            ? $this->flags | WITH_DEFAULTS
            : $this->flags;

        $data = [];
        foreach ($properties as $name => $property) {
            $propertyType = $this->propertyTypes[$name];
            $data[$name] = $propertyType->castValueToData($property, $flags);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param bool $withDefaults
     *
     * @return array
     */
    private function recursiveToArray(array $data, bool $withDefaults): array
    {
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $data[$name] = $this->recursiveToArray($value, $withDefaults);
            } elseif ($value instanceof self) {
                $data[$name] = $withDefaults
                    ? $value->toArrayWithDefaults()
                    : $value->toArray();
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
