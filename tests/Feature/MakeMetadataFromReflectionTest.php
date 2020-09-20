<?php

namespace Rexlabs\DataTransferObject\Tests\Feature;

use Rexlabs\DataTransferObject\Factory;
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
        $factory = new Factory([]);
        $classData = $factory->extractClassData(ExampleDataTransferObject::class);

        self::assertNotEmpty($classData->docComment);
        self::assertIsArray($classData->defaults);
        self::assertNotEmpty($classData->contents);
        self::assertNotEmpty($classData->namespace);
    }
}
