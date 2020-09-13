<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Property;

use function spl_object_id;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;

/**
 * Class DataTransferObjectTest
 */
class DataTransferObjectTest extends TestCase
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
        $object = new DataTransferObject(
            [
                'one' => $this->createMock(Property::class),
            ],
            ['one' => 'value'],
            NONE
        );

        self::assertEquals('value', $object->__get('one'));
    }


    /**
     * @test
     * @return void
     */
    public function setter_processes_value_with_property(): void
    {
        $type = $this->createMock(Property::class);
        $type->method('processValue')->willReturn('processed_value');

        $object = new DataTransferObject(
            ['blim' => $type],
            [],
            MUTABLE
        );

        $object->__set('blim', 'unprocessed_value');
        self::assertEquals('processed_value', $object->__get('blim'));
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
        $refDto = new ReflectionClass(DataTransferObject::class);
        $current = $refDto->getDefaultProperties()['baseFlags'];

        self::assertEquals($expected, $current);
    }

    /**
     * @test
     * @return void
     */
    public function to_array_handles_arrays_of_to_array_items(): void
    {
        $itemOne =  new DataTransferObject([], [
            'one' => 1,
            'two' => 2,
        ], NONE);
        $itemTwo =  new DataTransferObject([], [
            'one' => 1,
            'two' => 2,
        ], NONE);
        $parent = new DataTransferObject([], [
            'data' => [
                $itemOne,
                $itemTwo,
            ]
        ], NONE);

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
}
