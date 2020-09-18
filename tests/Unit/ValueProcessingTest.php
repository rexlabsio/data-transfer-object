<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingNestableDto;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\PARTIAL;

/**
 * Class ValueProcessingTest
 * @package Rexlabs\DataTransferObject
 */
class ValueProcessingTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function process_invalid_type_throws(): void
    {
        $propertyType = $this->factory->makePropertyType('', ['string']);

        $this->expectException(InvalidTypeError::class);
        $this->factory->processValue('test', $propertyType, null, MUTABLE);
    }

    /**
     * @test
     * @return void
     */
    public function process_value_does_not_change_simple_types(): void
    {
        $propertyType = $this->factory->makePropertyType('', ['mixed']);

        $values = [
            'blim',
            1234,
            true,
            null,
            [],
        ];

        foreach ($values as $value) {
            self::assertEquals($value, $this->factory->processValue('test', $propertyType, $value, MUTABLE));
        }
    }

    /**
     * @test
     * @return void
     */
    public function nested_data_cast_to_dto_type(): void
    {
        $propertyType = $this->factory->makePropertyType('one', [TestingNestableDto::class]);

        $castObject = $this->factory->processValue('test', $propertyType, [], MUTABLE | PARTIAL);

        self::assertInstanceOf(TestingNestableDto::class, $castObject);
    }

    /**
     * @test
     * @return void
     */
    public function nested_collection_data_cast_to_array_of_dto_type(): void
    {
        $propertyType = $this->factory->makePropertyType('one', [TestingNestableDto::class . '[]']);

        $dataObjects = [
            [], [], [],
        ];
        $castObjectCollection = $this->factory->processValue('test', $propertyType, $dataObjects, MUTABLE | PARTIAL);

        self::assertNotEmpty($castObjectCollection);
        self::assertCount(count($dataObjects), $castObjectCollection);
        foreach ($castObjectCollection as $castObject) {
            self::assertInstanceOf(TestingNestableDto::class, $castObject);
        }
    }
}
