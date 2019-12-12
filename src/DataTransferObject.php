<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use ReflectionException;

use function array_key_exists;
use function file_get_contents;

abstract class DataTransferObject implements Arrayable, Jsonable
{
    /** @var Collection[] */
    protected static $propertyTypes = [];

    /** @var array */
    protected $properties = [];

    /** @var array */
    private $initialisedKeys;

    /** @var int */
    private $flags;

    /**
     * ImmutableDataTransferObject constructor.
     * @param array $parameters
     * @param int $flags
     */
    public function __construct(array $parameters, int $flags = DTO::NONE)
    {
        $this->flags = $flags;

        $this->initialisedKeys = array_keys($parameters);

        foreach ($this->propertyTypes() as $name => $propertyType) {
            $hasParam = array_key_exists($name, $parameters);

            if (!$hasParam && !$propertyType->hasDefault() && !$propertyType->isNullable()) {
                throw DataTransferObjectError::uninitialized($name);
            }

            if ($hasParam) {
                $value = $parameters[$name];
            } elseif ($propertyType->hasDefault()) {
                $value = $propertyType->getDefault();
            }
            if (isset($value)) {
                $this->properties[$name] = $propertyType->processValue($name, $value);
                unset($value);
            }

            unset($parameters[$name]);
        }
        unset($name);

        if (count($parameters) > 0) {
            throw DataTransferObjectError::unknownProperties(array_keys($parameters));
        }
    }

    /**
     * @return Collection|Property[]
     */
    private function propertyTypes(): Collection
    {
        if (!array_key_exists(static::class, self::$propertyTypes)) {
            self::$propertyTypes[static::class] = $this->loadPropertyTypes(static::class);
        }

        return self::$propertyTypes[static::class];
    }

    public function __get(string $name)
    {
        if (!$this->propertyTypes()->has($name)) {
            throw DataTransferObjectError::unknownProperties([$name]);
        }

        return $this->properties[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {
        throw DataTransferObjectError::immutable($name);
    }

    public function __isset(string $name): bool
    {
        // return $this->propertyIsSet($name);
        return array_key_exists($name, $this->properties);
    }

    /**
     * Has this property been initialised
     *
     * Supports dot notation (no *)
     *
     * @param string|array $key
     * @return bool
     */
    public function propertyInitialised($key): bool
    {
        /**
         * @var array $keys
         */
        $keys = is_array($key) ? $key : explode('.', $key);

        /**
         * @var static|mixed $target
         */
        $target = $this;

        while (($segment = array_shift($keys)) !== null) {
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif ($this->objectPropertyInitialised($target, $segment)) {
                $target = $target->{$segment};
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $object
     * @param string $property
     * @return bool
     */
    private function objectPropertyInitialised($object, string $property): bool
    {
        if ($object instanceof self && in_array($property, $object->initialisedKeys, true)) {
            return true;
        }

        if (is_object($object) && isset($object->{$property})) {
            return true;
        }

        return false;
    }

    /**
     * Define default values for each properties
     *
     * @return array
     */
    protected static function getDefaults(): array
    {
        return [];
    }

    /**
     * @param string $class
     * @return Collection|Property[]
     */
    private function loadPropertyTypes(string $class): Collection
    {
        try {
            $refClass = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }

        $namespace = $refClass->getNamespaceName();
        $useStatements = $this->loadUseStatements($refClass->getFileName());
        $defaults = static::getDefaults();

        $docs = $refClass->getDocComment();
        $propertyPattern = <<<'REGEXP'
/@property-read\h+((?:[\w\\\_]+(?:\[])?\|?)+)\h+\$?([\w_]+)\b/
REGEXP;

        preg_match_all($propertyPattern, $docs, $propertyMatches, PREG_SET_ORDER);
        return collect($propertyMatches)
            ->mapWithKeys(function (array $matchSet) use (
                $defaults,
                $namespace,
                $useStatements
            ): ?array {
                if (!isset($matchSet[1], $matchSet[2])) {
                    return null;
                }
                [, $type, $name] = $matchSet;

                return [$name => new Property(
                    $namespace,
                    $useStatements,
                    $type,
                    array_key_exists($name, $defaults),
                    $defaults[$name] ?? null
                )];
            })
            ->filter()
            ->tap(function (Collection $types): void {
                if ($types->isEmpty()) {
                    throw new LogicException('No properties defined in phpdoc');
                }
            });
    }

    /**
     * @param string $fileName
     * @return Collection
     */
    private function loadUseStatements(string $fileName): Collection
    {
        $contents = file_get_contents($fileName);
        $top = Str::before($contents, "\nclass ");
        $usePattern = <<<'REGEXP'
/use\h+([\w\\\_|]+)\b(?:\h+as\h+([\w_]+))?;/i
REGEXP;
        preg_match_all($usePattern, $top, $useMatches, PREG_SET_ORDER);
        return collect($useMatches)
            ->mapWithKeys(function (array $useMatch): array {
                $fqcn = $useMatch[1];
                $name = $useMatch[2] ?? Arr::last(explode('\\', $fqcn));

                return [$name => $fqcn];
            });
    }

    public function all(): array
    {
        return $this->properties;
    }

    /**
     * Only values initially set (excludes defaults)
     *
     * @return array
     */
    public function allInitialised(): array
    {
        return Arr::only($this->properties, $this->initialisedKeys);
    }

    /**
     * Get only requested values from given dot notation keys (excludes defaults)
     *
     * @param array $keys
     *
     * @return array
     */
    public function only(array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(function (string $property, $key) {
                if (is_int($key)) {
                    $key = $property;
                }
                return [$key => data_get($this, $property)];
            })
            ->all();
    }

    /**
     * Get only initially set requested values from given dot notation keys (excludes defaults)
     *
     * @param array $keys
     * @return array
     */
    public function onlyInitialised(array $keys): array
    {
        return collect($keys)
            ->filter([$this, 'propertyInitialised'])
            ->mapWithKeys(function (string $property, $key) {
                if (is_int($key)) {
                    $key = $property;
                }
                return [$key => data_get($this, $property)];
            })
            ->all();
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    public function except(array $keys): array
    {
        return Arr::except($this->properties, $keys);
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

        return $properties;
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
}
