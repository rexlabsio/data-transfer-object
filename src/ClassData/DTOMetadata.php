<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\ClassData;

use Rexlabs\DataTransferObject\Type\PropertyType;
use Rexlabs\DataTransferObject\Type\PropertyReference;

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

    /** @var PropertyReference */
    public $ref;

    /** @var int */
    public $baseFlags;

    /**
     * DTOMetadata constructor.
     *
     * @param string $class
     * @param PropertyType[] $propertyTypes
     * @param PropertyReference $ref
     * @param int $baseFlags
     */
    public function __construct(
        string $class,
        array $propertyTypes,
        PropertyReference $ref,
        int $baseFlags
    ) {
        $this->class = $class;
        $this->propertyTypes = $propertyTypes;
        $this->ref = $ref;
        $this->baseFlags = $baseFlags;
    }
}
