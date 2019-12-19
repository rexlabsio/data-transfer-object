<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\FactoryContract;
use Rexlabs\DataTransferObject\Property;
use Rexlabs\DataTransferObject\Tests\TransferObjects\Other\TestingPhoneDto;
use Rexlabs\DataTransferObject\Tests\TransferObjects\TestingAddressDto;
use Rexlabs\DataTransferObject\Tests\TransferObjects\TestingPersonDto;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;

/**
 * Class PropertyTest
 * @package Rexlabs\DataTransferObject
 */
class PropertyTest extends TestCase
{
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
        $type = new Property($this->createMock(Factory::class), 'one', [], [], false, null);

        $this->expectException(ImmutableError::class);

        $type->processValue(null, NONE);
    }

    /**
     * @test
     * @return void
     */
    public function process_invalid_type_throws(): void
    {
        $this->expectException(InvalidTypeError::class);

        /**
         * @var MockObject|Property $type
         */
        $type = $this->getMockBuilder(Property::class)
            ->setConstructorArgs([$this->createMock(Factory::class), 'one', [], [], false, null])
            ->onlyMethods(['isValidType'])
            ->getMock();

        $type->method('isValidType')->willReturn(false);

        $type->processValue(null, MUTABLE);
    }

    /**
     * @test
     * @return void
     */
    public function process_value_does_not_change_simple_types(): void
    {
        /**
         * @var MockObject|Property $type
         */
        $type = $this->getMockBuilder(Property::class)
            ->setConstructorArgs([$this->createMock(Factory::class), 'one', [], [], false, null])
            ->onlyMethods(['isValidType'])
            ->getMock();

        $type->method('isValidType')->willReturn(true);

        $values = [
            'blim',
            1234,
            true,
            null,
            [],
        ];

        foreach ($values as $value) {
            $this->assertEquals($value, $type->processValue($value, MUTABLE));
        }
    }

    /**
     * @test
     * @return void
     */
    public function nested_data_cast_to_dto_type(): void
    {
        $nestedClass = $this->getMockClass(DataTransferObject::class);

        $factory = $this->createMock(FactoryContract::class);
        $factory->method('make')->willReturn(new $nestedClass([], [], NONE));

        $type = new Property($factory, 'one', [$nestedClass], [], false, null);

        $castObject = $type->processValue([], MUTABLE);

        $this->assertInstanceOf($nestedClass, $castObject);
    }

    /**
     * @test
     * @return void
     */
    public function nested_collection_data_cast_to_array_of_dto_type(): void
    {
        $nestedClass = $this->getMockClass(DataTransferObject::class);

        $factory = $this->createMock(FactoryContract::class);
        $factory->method('make')->willReturn(new $nestedClass([], [], NONE));

        $type = new Property($factory, 'one', [$nestedClass . '[]'], [$nestedClass], false, null);

        $dataObjects = [
            [], [], [],
        ];
        $castObjectCollection = $type->processValue($dataObjects, MUTABLE);

        $this->assertNotEmpty($castObjectCollection);
        $this->assertCount(count($dataObjects), $castObjectCollection);
        foreach ($castObjectCollection as $castObject) {
            $this->assertInstanceOf($nestedClass, $castObject);
        }
    }

    /**
     * TODO update to test at the appropriate level
     *
     * @test
     * @return void
     */
    public function cast_nested_objects(): void
    {
        $object = TestingPersonDto::make(
            [
                'first_name' => 'Joe',
                'phone' => [
                    'number' => '1234',
                ],
                'address' => [
                    'line_1' => '10 Drury Lane',
                ],
                'postal_address' => null,
            ]
        );

        $this->assertInstanceOf(TestingAddressDto::class, $object->address);
        $this->assertInstanceOf(TestingPhoneDto::class, $object->phone);
        $this->assertNull($object->postal_address);
    }
}
