<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;

class DataTransferObject implements Arrayable, Jsonable
{
    private const DEFAULT_FLAGS =
        Flags::ARRAY_DEFAULT_TO_EMPTY_ARRAY
        | Flags::NULLABLE_DEFAULT_TO_NULL;

    /** @var null|PropertyFactoryContract */
    private static $propertyFactory;

    /** @var Collection|Property[] Keyed by property name */
    protected $propertyTypes;

    /** @var Collection Keyed by property name */
    protected $properties;

    /** @var int Behaviour flags for DTOs, see Flags class */
    private $flags;

    /**
     * No validation or checking is done in constructor
     * Use `MyTransferObject::make($data)` instead
     *
     * @internal Use `MyTransferObject::make`
     *
     * @param Collection $propertyTypes keyed by property name
     * @param Collection $properties keyed by property name
     * @param int $flags
     */
    public function __construct(
        Collection $propertyTypes,
        Collection $properties,
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
    public static function make(array $parameters, int $flags = self::DEFAULT_FLAGS): self
    {
        $types = self::propertyFactory()->propertyTypes(static::class);

        $properties = collect($parameters)
            ->mapWithKeys(function ($value, string $name) use ($types, $flags): array {
                /**
                 * @var Property $type
                 */
                $type = $types->get($name);
                if ($type === null) {
                    // Ignore unknown types on lenient objects
                    if ($flags & Flags::IGNORE_UNKNOWN_PROPERTIES) {
                        return [];
                    }

                    throw new UnknownPropertiesError([$name]);
                }

                return [
                    $name => $type->processValue($value, $flags | Flags::MUTABLE)
                ];

            });

        // No default values or additional checks required for partial objects
        if ($flags & Flags::PARTIAL) {
            return new static($types, $properties, $flags);
        }

        // Set missing properties to defaults
        $defaults = $types->diffKeys($properties)
            ->mapWithKeys(function (Property $type) use ($flags): array {
                return $type->mapProcessedDefault($flags);
            });

        // Safe to merge because only missing keys were used to load defaults
        $properties = $properties->merge($defaults);

        // Find properties that are still missing after defaults
        $missing = $types->keys()->diff($properties->keys());
        if ($missing->isNotEmpty()) {
            throw new UninitialisedPropertiesError($missing->all());
        }

        return new static($types, $properties, $flags);
    }

    /**
     * Get the shared property factory. Caches class property data so each DTO's
     * docs are only parsed once
     *
     * @return PropertyFactoryContract
     */
    private static function propertyFactory(): PropertyFactoryContract
    {
        if (self::$propertyFactory === null) {
            self::setPropertyFactory(new PropertyFactory(collect([])));
        }

        return self::$propertyFactory;
    }

    /**
     * Override the default property factory used when DTOs are instantiated
     *
     * @param null|PropertyFactoryContract $propertyFactory
     * @return void
     */
    public static function setPropertyFactory(
        ?PropertyFactoryContract $propertyFactory
    ): void {
        self::$propertyFactory = $propertyFactory;
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

        return $this->properties->has($name)
            ? $this->properties->get($name)
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

        $this->properties->put($name, $processedValue);
    }

    /**
     * Get named type or throw type error if missing
     *
     * @param string $name
     * @return Property
     */
    private function type(string $name): Property
    {
        /**
         * @var Property $type
         */
        $type = $this->propertyTypes->get($name);
        if ($type === null) {
            throw new UnknownPropertiesError([$name]);
        }

        return $type;
    }

    public function __isset(string $name): bool
    {
        return $this->properties->has($name);
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties->all();
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
        $properties = $this->properties;
        foreach ($properties as $name => $value) {
            if ($value instanceof Arrayable) {
                $properties[$name] = $value->toArray();
            }
        }

        return $properties->toArray();
    }
}
