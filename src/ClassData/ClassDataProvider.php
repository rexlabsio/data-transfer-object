<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\ClassData;

use LogicException;
use ReflectionClass;
use ReflectionException;

use const Rexlabs\DataTransferObject\NONE;

class ClassDataProvider implements ClassDataProviderContract
{
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

    /**
     * @param string $class
     *
     * @return ClassData
     * @uses DataTransferObject::getDefaults();
     * @uses DataTransferObject::getCasts();
     *
     */
    public function getClassData(string $class): ClassData
    {
        try {
            $refClass = new ReflectionClass($class);
            $refGetDefaults = $refClass->getMethod('getDefaults');
            $refGetCasts = $refClass->getMethod('getCasts');
        } catch (ReflectionException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
        $refGetDefaults->setAccessible(true);
        $baseFlags = $refClass->getDefaultProperties()['baseFlags'] ?? NONE;
        $docComment = $refClass->getDocComment();

        if ($docComment === false) {
            throw new LogicException(
                sprintf(
                    'Class %s has no doc comment',
                    $class
                )
            );
        }

        $namespace = $refClass->getNamespaceName();
        $contents = file_get_contents($refClass->getFileName());
        $useStatements = $this->extractUseStatements($contents);
        $propertyTypesMap = $this->extractPropertyTypesMap($docComment, $namespace, $useStatements);

        return new ClassData(
            $namespace,
            $useStatements,
            $contents,
            $docComment,
            $refGetDefaults->getClosure($refClass)(),
            $propertyTypesMap,
            $refGetCasts->getClosure($refClass)(),
            $baseFlags
        );
    }

    /**
     * @param string $contents
     *
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

        return array_reduce(
            $useMatches,
            function (array $carry, array $useMatch): array {
                $fqcn = $useMatch[1];
                $classParts = explode('\\', $fqcn);
                $name = $useMatch[2] ?? end($classParts);

                $carry[$name] = $fqcn;

                return $carry;
            },
            []
        );
    }

    /**
     * @param string $docComment
     * @param null|string $namespace
     * @param array $useStatements
     *
     * @return string[][] ['property_name' => ['null', 'string']]
     */
    public function extractPropertyTypesMap(
        string $docComment,
        ?string $namespace,
        array $useStatements
    ): array {
        preg_match_all(
            self::PROPERTY_PATTERN,
            $docComment,
            $propertyMatches,
            PREG_SET_ORDER
        );

        $propertyTypesMap = [];
        foreach ($propertyMatches as $matchSet) {
            if (!isset($matchSet[1], $matchSet[2])) {
                continue;
            }
            [, $docType, $name] = $matchSet;

            $propertyTypesMap[$name] = array_map(
                function ($singleDocType) use ($namespace, $useStatements) {
                    return $this->parseDocType(
                        $singleDocType,
                        $namespace,
                        $useStatements
                    );
                },
                explode('|', $docType)
            );
        }

        if (count($propertyTypesMap) === 0) {
            throw new LogicException('No properties defined in phpdoc');
        }

        return $propertyTypesMap;
    }

    /**
     * @param string $type
     * @param null|string $namespace
     * @param array $useStatements
     *
     * @return string
     */
    public function parseDocType(string $type, ?string $namespace, array $useStatements): string
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
     * @param string $class
     *
     * @return bool
     */
    public function classExists(string $class): bool
    {
        return class_exists($class);
    }
}
