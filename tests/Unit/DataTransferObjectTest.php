<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use ReflectionClass;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use function spl_object_id;

use const Rexlabs\DataTransferObject\NONE;

/**
 * Class DataTransferObjectTest
 */
class DataTransferObjectTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function factory_created_implicitly(): void
    {
        $firstFactory = DataTransferObject::getFactory();

        DataTransferObject::setFactory(null);
        $secondFactory = DataTransferObject::getFactory();

        self::assertNotEquals(
            spl_object_id($firstFactory),
            spl_object_id($secondFactory),
            'DataTransferObject should have created a new factory instance'
        );
    }

    /**
     * @test
     * @return void
     */
    public function factory_loaded_once_and_cached(): void
    {
        self::assertEquals(
            spl_object_id(DataTransferObject::getFactory()),
            spl_object_id(DataTransferObject::getFactory())
        );
    }

    /**
     * @test
     * @return void
     */
    public function access_property_if_defined(): void
    {
        $metaData = $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'one' => ['string'],
            ]
        );

        $object = new TestDataTransferObject(
            $metaData->propertyTypes,
            ['one' => 'value'],
            [],
            NONE
        );

        self::assertEquals('value', $object->__get('one'));
    }

    /**
     * This test just ensures that the defaults don't change without bumping
     * the package version. Default behaviour is considered BC breaking.
     *
     * @test
     * @return void
     */
    public function base_flags_have_not_changed(): void
    {
        $expected = NONE;
        $refDto = new ReflectionClass(TestDataTransferObject::class);
        $current = $refDto->getDefaultProperties()['baseFlags'];

        self::assertEquals($expected, $current);
    }

    /**
     * @test
     *
     * @return void
     */
    public function to_array_handles_nullable_nested_dto(): void
    {
        $types = $this->factory->setClassMetadata(
            'item',
            [
                'data' => ['null', TestDataTransferObject::class],
            ]
        )->propertyTypes;

        $dto = new TestDataTransferObject(
            $types,
            [
                'data' => null,
            ],
            [],
            NONE
        );

        $expected = [
            'data' => null,
        ];

        self::assertEquals($expected, $dto->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function to_array_handles_arrays_of_to_array_items(): void
    {
        $parentTypes = $this->factory->setClassMetadata(
            'parent',
            [
                'data' => ['null', TestDataTransferObject::class . '[]'],
            ]
        )->propertyTypes;

        $types = $this->factory->setClassMetadata(
            'item',
            [
                'one' => ['int'],
                'two' => ['int'],
            ]
        )->propertyTypes;

        $itemOne = new TestDataTransferObject(
            $types,
            [
                'one' => 1,
                'two' => 2,
            ],
            [],
            NONE
        );
        $itemTwo = new TestDataTransferObject(
            $types,
            [
                'one' => 1,
                'two' => 2,
            ],
            [],
            NONE
        );
        $parent = new TestDataTransferObject(
            $parentTypes,
            [
                'data' => [
                    $itemOne,
                    $itemTwo,
                ],
            ],
            [],
            NONE
        );

        $expected = [
            'data' => [
                [
                    'one' => 1,
                    'two' => 2,
                ],
                [
                    'one' => 1,
                    'two' => 2,
                ],
            ],
        ];

        self::assertEquals($expected, $parent->toArray());
    }

    /**
     * @test
     */
    public function can_get_dto_keys(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'one' => ['string'],
            ]
        );


        self::assertEquals(['one'], TestDataTransferObject::getKeys());
    }
}
