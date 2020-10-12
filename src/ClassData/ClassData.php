<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\ClassData;

use Rexlabs\DataTransferObject\Type\PropertyCast;

/**
 * Class ClassData
 *
 * @package Rexlabs\DataTransferObject
 */
class ClassData
{
    /** @var string */
    private $namespace;

    /** @var string[] */
    private $useStatements;

    /** @var string */
    private $contents;

    /** @var string */
    private $docComment;

    /** @var array */
    private $defaults;

    /**
     * @var string[][] ['property_name' => ['null', 'string']]
     */
    private $propertyTypesMap;

    /**
     * @var PropertyCast[] ['param_name' => PropertyCast]
     */
    private $propertyCastMap;

    /** @var int */
    private $baseFlags;

    /**
     * ClassData constructor.
     *
     * @param string $namespace
     * @param string[] $useStatements
     * @param string $contents
     * @param string $docComment
     * @param array $defaults
     * @param string[][] $propertyTypesMap ['property_name' => ['null', 'string']]
     * @param PropertyCast[] $propertyCastMap ['param_name' => PropertyCast]
     * @param int $baseFlags
     */
    public function __construct(
        string $namespace,
        array $useStatements,
        string $contents,
        string $docComment,
        array $defaults,
        array $propertyTypesMap,
        array $propertyCastMap,
        int $baseFlags
    ) {
        $this->namespace = $namespace;
        $this->useStatements = $useStatements;
        $this->contents = $contents;
        $this->docComment = $docComment;
        $this->defaults = $defaults;
        $this->propertyTypesMap = $propertyTypesMap;
        $this->propertyCastMap = $propertyCastMap;
        $this->baseFlags = $baseFlags;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string[]
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->contents;
    }

    /**
     * @return string
     */
    public function getDocComment(): string
    {
        return $this->docComment;
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @return \string[][]
     */
    public function getPropertyTypesMap(): array
    {
        return $this->propertyTypesMap;
    }

    /**
     * @return PropertyCast[]
     */
    public function getPropertyCastMap(): array
    {
        return $this->propertyCastMap;
    }

    /**
     * @return int
     */
    public function getBaseFlags(): int
    {
        return $this->baseFlags;
    }
}
