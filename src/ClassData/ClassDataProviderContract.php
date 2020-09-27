<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\ClassData;

interface ClassDataProviderContract
{
    /**
     * Wrapped for easy mocking in tests
     *
     * @param string $class
     *
     * @return bool
     */
    public function classExists(string $class): bool;

    /**
     * @param string $class
     *
     * @return ClassData
     */
    public function getClassData(string $class): ClassData;
}
