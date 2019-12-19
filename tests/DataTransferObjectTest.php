<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
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

        DataTransferObject::setFactory(null);
    }

    /**
     * @test
     * @return void
     */
    public function factory_loaded_once_and_cached(): void
    {
        $this->assertEquals(
            spl_object_id(DataTransferObject::getFactory()),
            spl_object_id(DataTransferObject::getFactory())
        );
    }

    /**
     * @test
     * @return void
     */
    public function uses_value_if_set(): void
    {
        $object = new DataTransferObject(
            [
                'one' => $this->createMock(Property::class),
            ],
            ['one' => 'value'],
            NONE
        );

        $this->assertEquals('value', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function get_unknown_property_throws(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $object = new DataTransferObject([], [], NONE);

        $object->__get('blim');
    }

    /**
     * @test
     * @return void
     */
    public function set_unknown_property_throws(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $object = new DataTransferObject([], [], MUTABLE);

        $object->__set('blim', 'blam');
    }

    /**
     * @test
     * @return void
     */
    public function unset_property_reverts_to_default(): void
    {
        $type = $this->createMock(Property::class);
        $type->method('processDefault')->willReturn('blam');

        $object = new DataTransferObject(
            ['blim' => $type],
            [],
            NONE
        );

        $this->assertEquals('blam', $object->__get('blim'));
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
        $this->assertEquals('processed_value', $object->__get('blim'));
    }
}
