<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;

use function array_key_exists;
use function array_unshift;
use function class_exists;
use function file_get_contents;
use function sprintf;

/**
 * Class Factory
 * @package Rexlabs\DataTransferObject
 */
class Factory implements FactoryContract
{
    /**
     * Property doc pattern breakdown
     *
     * - Start with "@property" or "@property-read"
     * - Capture type name eg string with possible "[]" suffix
     * - Capture variable name "$foo" or "foo"
     * - Capture possible default value, anything after "="
     *   - Default value is parsed manually afterwards so errors can be thrown
     *     for ambiguous text
     */
    private const PROPERTY_PATTERN = <<<'REGEXP'
/@property(?:-read)?\h+((?:[\w\\\_]+(?:\[])?\|?)+)\h+\$?([\w_]+)\b(?:\h*=\h*(.*))?/
REGEXP;

    /**
     * Use statement pattern breakdown
     *
     * - Start with "use"
     * - Capture fully qualified class name eg Carbon\Carbon
     * - Capture possible class alias after "as"
     */
    private const USE_STATEMENT_PATTERN = <<<'REGEXP'
/use\h+\\?([\w\\\_|]+)\b(?:\h+as\h+([\w_]+))?;/i
REGEXP;

    private const SIMPLE_TYPES = [
        'int',
        'integer',
        'bool',
        'boolean',
        'float',
        'double',
        'true',
        'false',
        'null',
    ];

    /** @var DTOMetadata[] Keyed by class name */
    protected $classMetadata;

    /**
     * PropertyFactory constructor.
     * @param array $classPropertyTypes
     */
    public function __construct(array $classPropertyTypes)
    {
        $this->classMetadata = $classPropertyTypes;
    }

    /**
     * Make an instance of the requested DTO
     *
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public function make(string $class, array $parameters, int $flags): DataTransferObject
    {
        $meta = $this->getDTOMetadata($class);

        return $this->makeWithPropertyTypes(
            $meta->propertyTypes,
            $meta->class,
            $parameters,
            $meta->baseFlags | $flags
        );
    }

    /**
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once
     *
     * @param string $class
     * @return DTOMetadata
     */
    public function getDTOMetadata(string $class): DTOMetadata
    {
        $key = $this->getCacheKey('dto', $class, []);

        return $this->cacheGet($key, function () use ($class) {
            $classData = $this->extractClassData($class);
            $useStatements = $this->extractUseStatements($classData->contents);

            return new DTOMetadata(
                $class,
                $this->mapClassToPropertyTypes($classData, $useStatements),
                $classData->baseFlags
            );
        });
    }

    /**
     * @param string $key
     * @param callable $callable
     * @return DTOMetadata
     */
    private function cacheGet(string $key, callable $callable): DTOMetadata
    {
        if (!array_key_exists($key, $this->classMetadata)) {
            $this->classMetadata[$key] = $callable();
        }

        return $this->classMetadata[$key];
    }

    /**
     * @param string $prefix
     * @param string $class
     * @param array $args
     * @return string
     */
    private function getCacheKey(string $prefix, string $class, array $args): string
    {
        sort($args);
        array_unshift($args, $prefix, $class);
        return implode('_', $args);
    }

    /**
     * @param PropertyType[] $propertyTypes
     * @param string $class
     * @param array $parameters
     * @param int $flags
     *
     * @return DataTransferObject
     */
    public function makeWithPropertyTypes(
        array $propertyTypes,
        string $class,
        array $parameters,
        int $flags = NONE
    ): DataTransferObject {
        $properties = [];
        foreach ($parameters as $name => $value) {
            $propertyType = $propertyTypes[$name] ?? null;
            if ($propertyType === null) {
                // Ignore unknown types on lenient objects
                if ($flags & (IGNORE_UNKNOWN_PROPERTIES | TRACK_UNKNOWN_PROPERTIES)) {
                    continue;
                }

                throw new UnknownPropertiesError([$name]);
            }

            $properties[$name] = $this->processValue($propertyType, $value, $flags | MUTABLE);
        }

        $unknownProperties = ($flags & TRACK_UNKNOWN_PROPERTIES)
            ? array_diff_key($parameters, array_flip(array_keys($properties)))
            : [];

        // Only set defaults when explicitly requested
        if ($flags & DEFAULTS) {
            foreach ($propertyTypes as $propertyType) {
                // Property already provided
                if (array_key_exists($propertyType->getName(), $properties)) {
                    continue;
                }

                // Can't set defaults of the property type doesn't have one
                if (!$propertyType->hasValidDefault()) {
                    continue;
                }

                // Set the missing property to the default
                $properties[$propertyType->getName()] = $propertyType->getDefault();
            }
        }

        // Return before check for uninitialised properties for partial
        if ($flags & PARTIAL) {
            return new $class($propertyTypes, $properties, $flags);
        }

        // Find properties that are still missing after defaults
        $missing = array_diff(array_keys($propertyTypes), array_keys($properties));
        if (count($missing) > 0) {
            throw new UninitialisedPropertiesError($missing, $class);
        }

        /**
         * @var DataTransferObject $dto
         */
        $dto = new $class($propertyTypes, $properties, $flags);
        if ($flags & TRACK_UNKNOWN_PROPERTIES) {
            $dto->setUnknownProperties($unknownProperties);
        }

        return $dto;
    }

    /**
     * Check value is of valid type and optionally cast to a nested DTO
     *
     * @param PropertyType $propertyType
     * @param mixed $value
     * @param int $flags
     *
     * @return mixed
     */
    public function processValue(PropertyType $propertyType, $value, int $flags)
    {
        if (!($flags & MUTABLE)) {
            throw new ImmutableError($propertyType->getName());
        }

        if (is_array($value)) {
            $value = $this->shouldBeCastToCollection($value)
                ? $this->castCollection($propertyType, $value, $flags)
                : $this->cast($propertyType, $value, $flags);
        }

        if (!$propertyType->isValidValueForType($value)) {
            throw new InvalidTypeError($propertyType->getName(), $propertyType->getTypes(), $value);
        }

        return $value;
    }

    /**
     * @param array $values
     * @return bool
     */
    private function shouldBeCastToCollection(array $values): bool
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
     * @param PropertyType $propertyType
     * @param array $values
     * @param int $flags
     *
     * @return array
     */
    private function castCollection(PropertyType $propertyType, array $values, int $flags): array
    {
        /**
         * @var string|null $castTo
         */
        $castTo = null;

        // If multiple types are available this will only attempt to case
        // using the last valid type.
        foreach ($propertyType->getArrayTypes() as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        // No valid type found, unable to cast type
        // Return the values as they are
        if ($castTo === null) {
            return $values;
        }

        $castValues = [];

        foreach ($values as $value) {
            $castValues[] = call_user_func([$castTo, 'make'], $value, $flags);
        }

        return $castValues;
    }

    /**
     * @param PropertyType $propertyType
     * @param mixed $value
     * @param int $flags
     *
     * @return mixed|DataTransferObject
     */
    private function cast(PropertyType $propertyType, $value, int $flags)
    {
        /**
         * @var string|null $castTo
         */
        $castTo = null;

        // If multiple types are available this will only attempt to case
        // using the last valid type.
        foreach ($propertyType->getTypes() as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        // No valid type found, unable to cast type
        // Return the values as they are
        if ($castTo === null) {
            return $value;
        }

        return call_user_func([$castTo, 'make'], $value, $flags);
    }

    /**
     * @param string $class
     * @return ClassData
     */
    public function extractClassData(string $class): ClassData
    {
        try {
            $refClass = new ReflectionClass($class);
            $refGetDefaults = $refClass->getMethod('getDefaults');
        } catch (ReflectionException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
        $refGetDefaults->setAccessible(true);
        $baseFlags = $refClass->getDefaultProperties()['baseFlags'] ?? NONE;
        $docComment = $refClass->getDocComment();

        if ($docComment === false) {
            throw new LogicException(sprintf(
                'Class %s has no doc comment',
                $class
            ));
        }

        return new ClassData(
            $refClass->getNamespaceName(),
            file_get_contents($refClass->getFileName()),
            $docComment,
            $refGetDefaults->getClosure($refClass)(),
            $baseFlags
        );
    }

    /**
     * @param ClassData $classData
     * @param array $useStatements
     *
     * @return PropertyType[]
     */
    public function mapClassToPropertyTypes(ClassData $classData, array $useStatements): array
    {
        $allTypesByName = $this->mapAssoc(
            function (string $docType) use ($classData, $useStatements): array {
                return $this->mapTypes(
                    $classData->namespace,
                    $useStatements,
                    explode('|', $docType)
                );
            },
            $this->extractPropertyDocs($classData->docComment)
        );

        return $this->makePropertyTypes($allTypesByName, $classData->defaults);
    }

    /**
     * @param array $allTypesByName ['name' => ['all', 'types']]
     * @param array $classDefaults ['name' => 'default_value']
     *
     * @return PropertyType[]
     */
    public function makePropertyTypes(
        array $allTypesByName,
        array $classDefaults = []
    ): array {
        $propertyTypes = [];

        foreach ($allTypesByName as $name => $allTypes) {
            $propertyTypes[$name] = $this->makePropertyType($name, $allTypes, $classDefaults);
        }

        return $propertyTypes;
    }

    public function makePropertyType(
        string $name,
        array $allTypes,
        array $classDefaults = []
    ): PropertyType {
        if (empty($allTypes)) {
            throw new InvalidArgumentException(sprintf(
                'At least one type must be defined for property: %s',
                $name
            ));
        }

        $singleTypes = [];
        $arrayTypes = [];
        $isNullable = false;
        $isArray = false;
        $isBool = false;
        $hasValidDefault = false;
        $default = null;

        foreach ($allTypes as $type) {
            if ($type === 'null') {
                $isNullable = true;
            }

            if ($type === 'bool' || $type === PropertyType::TYPE_ALIASES['bool']) {
                $isBool = true;
            }

            if ($type === 'array') {
                $isArray = true;
            }

            if (substr($type, -2) === '[]') {
                $arrayTypes[] = substr($type, 0, -2);
                $isArray = true;
            } else {
                $singleTypes[] = $type;
            }
        }

        // Order of default cascading is important
        // Lower checks will override higher ones
        // Generally preference is for the "least meaningful" value to win
        // eg null will override false or empty array

        if ($isArray) {
            $hasValidDefault = true;
            $default = [];
        }

        if ($isBool) {
            $hasValidDefault = true;
            $default = false;
        }

        if ($isNullable) {
            $hasValidDefault = true;
            $default = null;
        }

        // Class default last to override any implicit defaults
        if (array_key_exists($name, $classDefaults)) {
            $hasValidDefault = true;

            // TODO type check default and throw if invalid
            $default = $classDefaults[$name];
        }

        return new PropertyType(
            $name,
            $singleTypes,
            $arrayTypes,
            $isNullable,
            $isBool,
            $isArray,
            $hasValidDefault,
            $default
        );
    }

    /**
     * @param string $docComment
     * @return string[] [name => docType]
     */
    public function extractPropertyDocs(string $docComment): array
    {
        preg_match_all(
            self::PROPERTY_PATTERN,
            $docComment,
            $propertyMatches,
            PREG_SET_ORDER
        );

        $propertyTypes = array_reduce(
            $propertyMatches,
            function (array $carry, array $matchSet): array {
                if (!isset($matchSet[1], $matchSet[2])) {
                    return $carry;
                }
                [, $docType, $name] = $matchSet;

                $carry[$name] = $docType;
                return $carry;
            },
            []
        );

        if (count($propertyTypes) === 0) {
            throw new LogicException('No properties defined in phpdoc');
        }

        return $propertyTypes;
    }

    /**
     * @param null|string $namespace
     * @param string[] $useStatements
     * @param array $rawTypes
     * @return array
     */
    private function mapTypes(
        ?string $namespace,
        array $useStatements,
        array $rawTypes
    ): array {
        return array_map(function (string $type) use ($namespace, $useStatements): string {
            return $this->mapType($type, $namespace, $useStatements);
        }, $rawTypes);
    }

    /**
     * @param string $type
     * @param null|string $namespace
     * @param array $useStatements
     * @return string
     */
    public function mapType(string $type, ?string $namespace, array $useStatements): string
    {
        // Remove the array suffix so it can be reapplied at the end
        if (substr($type, -2) === '[]') {
            $realType = substr($type, 0, -2);
            $suffix = '[]';
        } else {
            $suffix = '';
            $realType = $type;
        }

        // Check for simple types first
        if (in_array($realType, self::SIMPLE_TYPES, true)) {
            return $realType . $suffix;
        }

        // Fully qualified class name exists
        if (strpos($realType, '\\') === 0 && $this->classExists($realType)) {
            return substr($realType, 1) . $suffix;
        }

        // Found class or alias in use statement
        if (array_key_exists($realType, $useStatements)) {
            return $useStatements[$realType] . $suffix;
        }

        // Found a class in this namespace
        $thisNamespaceClass = sprintf('%s\\%s', $namespace, $realType);
        if ($this->classExists($thisNamespaceClass)) {
            return $thisNamespaceClass . $suffix;
        }

        // Attempt basic class name or primitive type
        return $realType . $suffix;
    }

    /**
     * Wrapped for easy mocking in tests
     *
     * @param string $type
     * @return bool
     */
    public function classExists(string $type): bool
    {
        return class_exists($type);
    }

    /**
     * @param string $contents
     * @return string[]
     */
    public function extractUseStatements(string $contents): array
    {
        $top = explode("\nclass ", $contents)[0];

        preg_match_all(
            self::USE_STATEMENT_PATTERN,
            $top,
            $useMatches,
            PREG_SET_ORDER
        );

        return array_reduce($useMatches, function (array $carry, array $useMatch): array {
            $fqcn = $useMatch[1];
            $classParts = explode('\\', $fqcn);
            $name = $useMatch[2] ?? end($classParts);

            $carry[$name] = $fqcn;

            return $carry;
        }, []);
    }

    /**
     * @param callable $callback
     * @param array $items
     * @return array
     */
    private function mapAssoc(callable $callback, array $items): array
    {
        $keys = array_keys($items);

        $mappedItems = array_map($callback, $items, $keys);

        return array_combine($keys, $mappedItems);
    }
}
