<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
use Rexlabs\DataTransferObject\Factory;

use const Rexlabs\DataTransferObject\IGNORE_UNKNOWN_PROPERTIES;
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\TRACK_UNKNOWN_PROPERTIES;

class UnknownPropertiesTest extends TestCase
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
    public function get_unknown_property_throws(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $object = new DataTransferObject([], [], [], NONE);

        $object->__get('blim');
    }

    /**
     * @test
     * @return void
     */
    public function set_unknown_property_throws(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $object = new DataTransferObject([], [], [], MUTABLE);

        $object->__set('blim', 'blam');
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_throw_error(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            [],
            ['blim' => 'blam'],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_ignore_flags(): void
    {
        $object = $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            [],
            ['blim' => 'blam'],
            IGNORE_UNKNOWN_PROPERTIES
        );

        self::assertEquals([], $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_track_flag(): void
    {
        $object = $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            [],
            ['blim' => 'blam'],
            TRACK_UNKNOWN_PROPERTIES
        );

        self::assertEquals([], $object->toArray());
    }

    /**
     * @test
     *
     * @return void
     */
    public function cannot_query_unknown_properties_with_ignore_flag(): void
    {
        $unknownProperties = ['blim' => 'blam'];
        $object = $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            [],
            $unknownProperties,
            IGNORE_UNKNOWN_PROPERTIES
        );

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
        $unknownProperties = ['blim' => 'blam'];
        $object = $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            [],
            $unknownProperties,
            TRACK_UNKNOWN_PROPERTIES
        );

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
        $object = $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            [],
            ['blim' => 'blam'],
            TRACK_UNKNOWN_PROPERTIES
        );

        self::assertEquals([], $object->toArray());
        self::assertEquals(['blim'], $object->getUnknownPropertyNames());
    }
}
