<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

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
     * @param array $propertyNames
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public static function makeRecord(
        array $propertyNames,
        array $parameters,
        int $flags = NONE
    ): DataTransferObject {
        return self::getFactory()->makeRecord(
            static::class,
            $parameters,
            $propertyNames,
            $flags
        );
    }

    /**
     * @param array $propertyNames
     * @param array $parameters
     * @param int $flags
     * @return static
     */
    public static function makePick(
        array $propertyNames,
        array $parameters,
        int $flags = NONE
    ): self {
        return self::getFactory()->makePick(
            static::class,
            $parameters,
            $propertyNames,
            $flags
        );
    }

    /**
     * @param array $propertyNames
     * @param array $parameters
     * @param int $flags
     * @return static
     */
    public static function makeOmit(
        array $propertyNames,
        array $parameters,
        int $flags = NONE
    ): self {
        return self::getFactory()->makeOmit(
            static::class,
            $parameters,
            $propertyNames,
            $flags
        );
    }

    /**
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return static
     */
    public static function makeExtract(
        string $class,
        array $parameters,
        int $flags = NONE
    ): self {
        return self::getFactory()->makeExtract(
            static::class,
            $class,
            $parameters,
            $flags
        );
    }

    /**
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return static
     */
    public static function makeExclude(
        string $class,
        array $parameters,
        int $flags = NONE
    ): self {
        return self::getFactory()->makeExclude(
            static::class,
            $class,
            $parameters,
            $flags
        );
    }

    /**
     * Get the shared property factory. Caches class property data so each DTO's
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
     *
     * @param string $name
     * @return bool
     */
    public function isDefined(string $name): bool
    {
        // Check typename is valid
        $this->type($name);

        return array_key_exists($name, $this->properties);
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param int $options
     * @param int $depth
     * @return string
     */
    public function toJson($options = 0, $depth = 512): string
    {
        return json_encode($this->properties, $options, $depth);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $properties = $this->properties;
        foreach ($properties as $name => $value) {
            if (method_exists($value, 'toArray')) {
                $properties[$name] = $value->toArray();
            }
        }

        return $properties;
    }
}
