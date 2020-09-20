<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use ReflectionClass;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use function spl_object_id;

use const Rexlabs\DataTransferObject\MUTABLE;
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
        $object = new TestDataTransferObject(
            ['one' => $this->factory->makePropertyType('one', ['string'])],
            ['one' => 'value'],
            [],
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
        $factory = $this->createMock(Factory::class);
        $factory->method('processValue')->willReturn('processed_value');

        $object = new TestDataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['string'])],
            [],
            [],
            MUTABLE
        );
        DataTransferObject::setFactory($factory);

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
        $refDto = new ReflectionClass(TestDataTransferObject::class);
        $current = $refDto->getDefaultProperties()['baseFlags'];

        self::assertEquals($expected, $current);
    }

    /**
     * @test
     * @return void
     */
    public function to_array_handles_arrays_of_to_array_items(): void
    {
        $itemOne =  new TestDataTransferObject(
            [],
            [
            'one' => 1,
            'two' => 2,
            ],
            [],
            NONE
        );
        $itemTwo =  new TestDataTransferObject(
            [],
            [
            'one' => 1,
            'two' => 2,
            ],
            [],
            NONE
        );
        $parent = new TestDataTransferObject(
            [],
            [
            'data' => [
                $itemOne,
                $itemTwo,
            ]
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
}
