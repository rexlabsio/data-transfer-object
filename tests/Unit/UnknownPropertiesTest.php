<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\IGNORE_UNKNOWN_PROPERTIES;
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\TRACK_UNKNOWN_PROPERTIES;

class UnknownPropertiesTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function get_unknown_property_throws(): void
    {
        $this->expectException(UnknownPropertiesTypeError::class);

        $object = new TestDataTransferObject([], [], [], NONE);

        $object->__get('blim');
    }

    /**
     * @test
     * @return void
     */
    public function set_unknown_property_throws(): void
    {
        $this->expectException(UnknownPropertiesTypeError::class);

        $object = new TestDataTransferObject([], [], [], MUTABLE);

        $object->__set('blim', 'blam');
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_throw_error(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            []
        );

        $this->expectException(UnknownPropertiesTypeError::class);

        TestDataTransferObject::make(['blim' => 'blam'], NONE);
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_ignore_flags(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            []
        );

        $object = TestDataTransferObject::make(['blim' => 'blam'], IGNORE_UNKNOWN_PROPERTIES);

        self::assertEquals([], $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_track_flag(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            []
        );

        $object = TestDataTransferObject::make(['blim' => 'blam'], TRACK_UNKNOWN_PROPERTIES);

        self::assertEquals([], $object->toArray());
    }

    /**
     * @test
     *
     * @return void
     */
    public function cannot_query_unknown_properties_with_ignore_flag(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            []
        );

        $unknownProperties = ['blim' => 'blam'];

        $object = TestDataTransferObject::make($unknownProperties, IGNORE_UNKNOWN_PROPERTIES);

        self::assertEquals([], $object->toArray());
        self::assertEmpty($object->getUnknownProperties());
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_query_unknown_properties_with_track_flag(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            []
        );

        $unknownProperties = ['blim' => 'blam'];
        $object = TestDataTransferObject::make($unknownProperties, TRACK_UNKNOWN_PROPERTIES);

        self::assertEquals([], $object->toArray());
        self::assertEquals($unknownProperties, $object->getUnknownProperties());
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_query_unknown_property_names_with_track_flag(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            []
        );

        $object = TestDataTransferObject::make(['blim' => 'blam'], TRACK_UNKNOWN_PROPERTIES);

        self::assertEquals([], $object->toArray());
        self::assertEquals(['blim'], $object->getUnknownPropertyNames());
    }
}
