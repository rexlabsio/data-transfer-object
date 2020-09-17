<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\PropertyType;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingNestableDto;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

/**
 * Class ValueProcessingTest
 * @package Rexlabs\DataTransferObject
 */
class ValueProcessingTest extends TestCase
{
    /** @var Factory */
    private $factory;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory([]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        // Clear cached static data
        // Also I'm sorry for caching static data
        DataTransferObject::setFactory(null);
    }

    /**
     * @test
     * @return void
     */
    public function process_immutable_throws(): void
    {
        $propertyType = $this->factory->makePropertyType('', ['mixed']);

        $this->expectException(ImmutableError::class);

        $this->factory->processValue($propertyType, null, NONE);
    }

    /**
     * @test
     * @return void
     */
    public function process_invalid_type_throws(): void
    {
        $propertyType = $this->factory->makePropertyType('', ['string']);

        $this->expectException(InvalidTypeError::class);
        $this->factory->processValue($propertyType, null, MUTABLE);
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
            self::assertEquals($value, $this->factory->processValue($propertyType, $value, MUTABLE));
        }
    }

    /**
     * @test
     * @return void
     */
    public function nested_data_cast_to_dto_type(): void
    {
        $propertyType = $this->factory->makePropertyType('one', [TestingNestableDto::class]);

        $castObject = $this->factory->processValue($propertyType, [], MUTABLE | PARTIAL);

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
        $castObjectCollection = $this->factory->processValue($propertyType, $dataObjects, MUTABLE | PARTIAL);

        self::assertNotEmpty($castObjectCollection);
        self::assertCount(count($dataObjects), $castObjectCollection);
        foreach ($castObjectCollection as $castObject) {
            self::assertInstanceOf(TestingNestableDto::class, $castObject);
        }
    }
}
