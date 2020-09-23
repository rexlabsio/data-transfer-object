<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\ClassData;

use Rexlabs\DataTransferObject\Type\PropertyType;

/**
 * Class DTOMetadata
 *
 * @package Rexlabs\DataTransferObject
 */
class DTOMetadata
{
    /** @var string */
    public $class;

    /** @var PropertyType[] */
    public $propertyTypes;

    /** @var int */
    public $baseFlags;

    /**
     * DTOMetadata constructor.
     *
     * @param string $class
     * @param PropertyType[] $propertyTypes
     * @param int $baseFlags
     */
    public function __construct(string $class, array $propertyTypes, int $baseFlags)
    {
        $this->class = $class;
        $this->propertyTypes = $propertyTypes;
        $this->baseFlags = $baseFlags;
    }
}
