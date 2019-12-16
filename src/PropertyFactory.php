<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use ReflectionException;

use function file_get_contents;
use function array_key_exists;

/**
 * Class PropertyFactory
 * @package Rexlabs\DataTransferObject
 */
class PropertyFactory implements PropertyFactoryContract
{
    private const PROPERTY_PATTERN = <<<'REGEXP'
/@property(?:-read)?\h+((?:[\w\\\_]+(?:\[])?\|?)+)\h+\$?([\w_]+)\b/
REGEXP;

    /** @var Collection[] */
    private $classPropertyTypes;

    /**
     * PropertyFactory constructor.
     * @param Collection $classPropertyTypes
     */
    public function __construct(Collection $classPropertyTypes)
    {
        $this->classPropertyTypes = $classPropertyTypes;
    }

    /**
     * Get collection of properties for a DTO. Use a simple cache to ensure each
     * class doc is only parsed once.
     *
     * @param string $class
     * @return Collection|Property[]
     */
    public function propertyTypes(string $class): Collection
    {
        if (!$this->classPropertyTypes->has($class)) {
            $this->classPropertyTypes->put($class, $this->loadPropertyTypes($class));
        }

        // Return a unique copy of the collection as a safety precaution
        // Property classes in the collection will still be the same instances
        // but that class is immutable so it should be ok
        return clone $this->classPropertyTypes->get($class);
    }

    /**
     * @param string $class
     * @return Collection|Property[] Keyed by name
     */
    private function loadPropertyTypes(string $class): Collection
    {
        $classData = $this->loadClassData($class);

        return $this->extractDocPropertyTypes($classData->docComment)
            ->map(function (string $docType, string $name) use ($classData): Property {
                return $this->makeProperty(
                    $name,
                    $docType,
                    $classData
                );
            });
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

        return new ClassData(
            $refClass->getNamespaceName(),
            $this->loadUseStatements($refClass->getFileName()),
            $refGetDefaults->getClosure($refClass)(),
            $refClass->getDocComment()
        );
    }

    /**
     * @param string $docComment
     * @return Collection|string[] [name => docType]
     */
    public function extractDocPropertyTypes(string $docComment): Collection
    {
        preg_match_all(
            self::PROPERTY_PATTERN,
            $docComment,
            $propertyMatches,
            PREG_SET_ORDER
        );

        return collect($propertyMatches)
            ->mapWithKeys(function (array $matchSet): array {
                if (!isset($matchSet[1], $matchSet[2])) {
                    return [];
                }
                [, $docType, $name] = $matchSet;

                return [$name => $docType];
            })
            ->tap(function (Collection $types): void {
                if ($types->isEmpty()) {
                    throw new LogicException('No properties defined in phpdoc');
                }
            });
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
            array_filter(
                $types,
                function (string $type) {
                    return Str::endsWith($type, '[]') || $type === 'array';
                }
            )
        );
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
            ->mapWithKeys(
                function (array $useMatch): array {
                    $fqcn = $useMatch[1];
                    $name = $useMatch[2] ?? Arr::last(explode('\\', $fqcn));

                    return [$name => $fqcn];
                }
            );
    }
}
