<?php

namespace Rexlabs\DataTransferObject\Tests\Feature;

use Rexlabs\DataTransferObject\ClassData\ClassDataProvider;
use Rexlabs\DataTransferObject\Tests\Support\ExampleDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

/**
 * Class MakeMetadataFromReflectionTest
 *
 * @package Rexlabs\DataTransferObject\Tests\Feature
 */
class MakeMetadataFromReflectionTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function load_class_data_from_class_name(): void
    {
        $provider = new ClassDataProvider();
        $classData = $provider->getClassData(ExampleDataTransferObject::class);

        self::assertNotEmpty($classData->getDocComment());
        self::assertIsArray($classData->getDefaults());
        self::assertIsArray($classData->getPropertyCastMap());
        self::assertNotEmpty($classData->getContents());
        self::assertNotEmpty($classData->getNamespace());
    }
}
