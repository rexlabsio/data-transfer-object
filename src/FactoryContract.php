<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

interface FactoryContract
{
    /**
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once
     *
     * @param string $class
     * @return DTOMetadata
     */
    public function getClassMetadata(string $class): DTOMetadata;

    /**
     * @param DTOMetadata $meta
     *
     * @return void
     */
    public function setClassMetadata(DTOMetadata $meta): void;
}
