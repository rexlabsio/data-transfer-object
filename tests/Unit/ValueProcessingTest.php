<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

/**
 * Class ValueProcessingTest
 *
 * @package Rexlabs\DataTransferObject
 */
class ValueProcessingTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function process_value_does_not_change_simple_types(): void
    {
        $propertyType = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'test' => ['mixed'],
                ]
            )
            ->propertyTypes['test'];

        $values = [
            'blim',
            1234,
            true,
            null,
            [],
        ];

        foreach ($values as $value) {
            self::assertEquals($value, $propertyType->castValueToType($value, NONE));
        }
    }

    /**
     * @test
     * @return void
     */
    public function nested_data_cast_to_dto_type(): void
    {
        $propertyType = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'test' => [TestDataTransferObject::class],
                ]
            )
            ->propertyTypes['test'];

        $castObject = $propertyType->castValueToType([], PARTIAL);

        self::assertInstanceOf(TestDataTransferObject::class, $castObject);
    }

    /**
     * @test
     * @return void
     */
    public function nested_collection_data_cast_to_array_of_dto_type(): void
    {
        $propertyType = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'test' => [TestDataTransferObject::class . '[]'],
                ]
            )
            ->propertyTypes['test'];

        $dataObjects = [
            [],
            [],
            [],
        ];
        $castObjectCollection = $propertyType->castValueToType($dataObjects, PARTIAL);

        self::assertNotEmpty($castObjectCollection);
        self::assertCount(count($dataObjects), $castObjectCollection);
        foreach ($castObjectCollection as $castObject) {
            self::assertInstanceOf(TestDataTransferObject::class, $castObject);
        }
    }
}
