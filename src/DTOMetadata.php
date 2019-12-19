<?php

namespace Rexlabs\DataTransferObject;

/**
 * Class DTOMetadata
 * @package Rexlabs\DataTransferObject
 */
class DTOMetadata
{
    /** @var Property[] */
    public $propertyTypes;

    /** @var int */
    public $defaultFlags;

    /**
     * DTOMetadata constructor.
     * @param array $propertyTypes
     * @param int $defaultFlags
     */
    public function __construct(array $propertyTypes, int $defaultFlags)
    {
        $this->propertyTypes = $propertyTypes;
        $this->defaultFlags = $defaultFlags;
    }
}
