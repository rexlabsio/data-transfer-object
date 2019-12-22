<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
use Rexlabs\DataTransferObject\Property;

use function spl_object_id;

use const Rexlabs\DataTransferObject\ARRAY_DEFAULT_TO_EMPTY_ARRAY;
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\NULLABLE_DEFAULT_TO_NULL;

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

    /**
     * This test just ensures that the defaults don't change without bumping
     * the package version. Default behaviour is considered BC breaking.
     *
     * @test
     * @return void
     * @throws ReflectionException
     */
    public function default_flags_have_not_changed(): void
    {
        $expected = NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY;
        $refDto = new ReflectionClass(DataTransferObject::class);
        $current = $refDto->getDefaultProperties()['defaultFlags'];

        $this->assertEquals($expected, $current);
    }
}
