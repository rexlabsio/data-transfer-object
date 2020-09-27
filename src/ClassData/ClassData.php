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
    public $namespace;

    /** @var string[] */
    public $useStatements;

    /** @var string */
    public $contents;

    /** @var string */
    public $docComment;

    /** @var array */
    public $defaults;

    /**
     * @var string[][] ['property_name' => ['null', 'string']]
     */
    public $propertyTypesMap;

    /**
     * @var PropertyCast[] ['param_name' => PropertyCast]
     */
    public $propertyCastMap;

    /** @var int */
    public $baseFlags;

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
}
