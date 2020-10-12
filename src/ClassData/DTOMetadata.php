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
    private $class;

    /** @var PropertyType[] */
    private $propertyTypes;

    /** @var PropertyReference */
    private $ref;

    /** @var int */
    private $baseFlags;

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

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return PropertyType[]
     */
    public function getPropertyTypes(): array
    {
        return $this->propertyTypes;
    }

    /**
     * @return PropertyReference
     */
    public function getRef(): PropertyReference
    {
        return $this->ref;
    }

    /**
     * @return int
     */
    public function getBaseFlags(): int
    {
        return $this->baseFlags;
    }
}
