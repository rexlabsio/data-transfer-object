<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use LogicException;
use ReflectionClass;
use ReflectionException;
use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;

use function array_key_exists;
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
/use\h+([\w\\\_|]+)\b(?:\h+as\h+([\w_]+))?;/i
REGEXP;

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
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once.
     * @param string $class
     * @return DTOMetadata
     */
    public function getClassMetadata(string $class): DTOMetadata
    {
        if (!array_key_exists($class, $this->classMetadata)) {
            $this->classMetadata[$class] = $this->loadDTOMetadata($class);
        }

        return $this->classMetadata[$class];
    }

    /**
     * Make an instance of the requested DTO
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return DataTransferObject
     */
    public function make(string $class, array $parameters, int $flags): DataTransferObject
    {
        $meta = $this->getClassMetadata($class);
        $types = $meta->propertyTypes;
        $flags = $meta->defaultFlags | $flags;

        return $this->makeWithProperties($types, $class, $parameters, $flags);
    }

    /**
     * @param Property[] $types
     * @param string $class
     * @param array $parameters
     * @param int $flags
     * @return mixed
     */
    public function makeWithProperties(array $types, string $class, array $parameters, int $flags)
    {
        $properties = array_reduce(
            array_keys($parameters),
            function (array $carry, string $name) use ($types, $flags, $parameters): array {
                $value = $parameters[$name];
                /**
                 * @var null|Property $type
                 */
                $type = $types[$name] ?? null;
                if ($type === null) {
                    // Ignore unknown types on lenient objects
                    if ($flags & IGNORE_UNKNOWN_PROPERTIES) {
                        return $carry;
                    }

                    throw new UnknownPropertiesError([$name]);
                }

                $carry[$name] = $type->processValue($value, $flags | MUTABLE);
                return $carry;
            },
            []
        );

        // No default values or additional checks required for partial objects
        if ($flags & PARTIAL) {
            return new $class($types, $properties, $flags);
        }

        // Set missing properties to defaults
        $defaults = array_reduce(
            array_diff_key($types, $properties),
            function (array $carry, Property $type) use ($flags): array {
                foreach ($type->mapProcessedDefault($flags) as $name => $default) {
                    $carry[$name] = $default;
                }
                return $carry;
            },
            []
        );

        // Safe to merge because only missing keys were used to load defaults
        $properties = array_merge($defaults, $properties);

        // Find properties that are still missing after defaults
        $missing = array_diff(array_keys($types), array_keys($properties));
        if (count($missing) > 0) {
            throw new UninitialisedPropertiesError($missing, static::class);
        }

        return new $class($types, $properties, $flags);
    }

    /**
     * @param string $class
     * @return DTOMetadata
     */
    private function loadDTOMetadata(string $class): DTOMetadata
    {
        $classData = $this->loadClassData($class);

        $types = $this->mapAssoc(
            function (string $docType, string $name) use ($classData): Property {
                return $this->makeProperty(
                    $name,
                    $docType,
                    $classData
                );
            },
            $this->extractDocPropertyTypes($classData->docComment)
        );

        return new DTOMetadata(
            $types,
            $classData->defaultFlags
        );
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

    /**
     * @param string $class
     * @return ClassData
     */
    public function loadClassData(string $class): ClassData
    {
        try {
            $refClass = new ReflectionClass($class);
            $refGetDefaults = $refClass->getMethod('getDefaults');
        } catch (ReflectionException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
        $refGetDefaults->setAccessible(true);
        $defaultFlags = $refClass->getDefaultProperties()['defaultFlags'] ?? NONE;
        $docComment = $refClass->getDocComment();

        if ($docComment === false) {
            throw new LogicException(sprintf(
                'Class %s has no doc comment',
                $class
            ));
        }

        return new ClassData(
            $refClass->getNamespaceName(),
            $this->loadUseStatements($refClass->getFileName()),
            $docComment,
            $refGetDefaults->getClosure($refClass)(),
            $defaultFlags
        );
    }

    /**
     * @param string $docComment
     * @return string[] [name => docType]
     */
    public function extractDocPropertyTypes(string $docComment): array
    {
        preg_match_all(
            self::PROPERTY_PATTERN,
            $docComment,
            $propertyMatches,
            PREG_SET_ORDER
        );

        $types = array_reduce(
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

        if (count($types) === 0) {
            throw new LogicException('No properties defined in phpdoc');
        }

        return $types;
    }

    /**
     * @param string $name
     * @param string $docType
     * @param ClassData $classData
     * @return Property
     */
    public function makeProperty(
        string $name,
        string $docType,
        ClassData $classData
    ): Property {
        $types = $this->mapTypes(
            $classData->namespace,
            $classData->useStatements,
            explode('|', $docType)
        );

        $arrayTypes = $this->mapArrayTypes($types);

        return new Property(
            $this,
            $name,
            $types,
            $arrayTypes,
            array_key_exists($name, $classData->defaults),
            $defaults[$name] ?? null
        );
    }

    /**
     * @param array $types
     * @return array
     */
    private function mapArrayTypes(array $types): array
    {
        return str_replace(
            '[]',
            '',
            array_filter($types, function (string $type) {
                return Str::endsWith($type, '[]') || $type === 'array';
            })
        );
    }

    /**
     * @param null|string $namespace
     * @param string[] $useStatements
     * @param array $types
     * @return array
     */
    private function mapTypes(
        ?string $namespace,
        array $useStatements,
        array $types
    ): array {
        return array_map(function (string $type) use ($namespace, $useStatements): string {
            // Found class or alias in use statement
            if (array_key_exists($type, $useStatements)) {
                return $useStatements[$type];
            }

            // Found a class in this namespace
            $thisNamespaceClass = sprintf('%s\\%s', $namespace, $type);
            if (class_exists($thisNamespaceClass)) {
                return $thisNamespaceClass;
            }

            // Attempt basic class name or primitive type
            return $type;
        }, $types);
    }

    /**
     * @param string $fileName
     * @return string[]
     */
    private function loadUseStatements(string $fileName): array
    {
        $contents = file_get_contents($fileName);
        $top = Str::before($contents, "\nclass ");

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
}
