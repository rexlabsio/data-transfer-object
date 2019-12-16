<?php

namespace Rexlabs\DataTransferObject;

use Illuminate\Support\Collection;

interface PropertyFactoryContract
{
    /**
     * Get collection of properties for a DTO. Use a simple cache to ensure each
     * class doc is only parsed once.
     *
     * @param string $class
     * @return Collection|Property[]
     */
    public function propertyTypes(string $class): Collection;
}
