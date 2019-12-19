<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
use Rexlabs\DataTransferObject\Property;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\Tests\TransferObjects\TestingPersonDto;

use function spl_object_id;

use const Rexlabs\DataTransferObject\ARRAY_DEFAULT_TO_EMPTY_ARRAY;
use const Rexlabs\DataTransferObject\IGNORE_UNKNOWN_PROPERTIES;
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\NULLABLE_DEFAULT_TO_NULL;
use const Rexlabs\DataTransferObject\PARTIAL;

/**
 * Class FactoryTest
 * @package Rexlabs\DataTransferObject
 */
class FactoryTest extends TestCase
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
    public function load_class_data_from_class_name(): void
    {
        $classData = $this->factory->loadClassData(TestingPersonDto::class);

        $this->assertNotEmpty($classData->docComment);
        $this->assertNotEmpty($classData->defaults);
        $this->assertNotEmpty($classData->useStatements);
        $this->assertNotEmpty($classData->namespace);
    }

    /**
     * @test
     * @return void
     */
    public function caches_loaded_metadata(): void
    {
        $meta = new DTOMetadata([], NONE);

        $factory = new Factory(['classOne' => $meta]);

        $newMeta = $factory->getClassMetadata('classOne');

        $this->assertEquals(spl_object_id($meta), spl_object_id($newMeta));
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_set(): void
    {
        $properties = [
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
            'nullable' => null,
        ];

        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['string'], [], false, null),
                'two' => new Property($this->factory, 'two', ['string'], [], false, null),
                'three' => new Property($this->factory, 'three', ['string'], [], false, null),
                'nullable' => new Property($this->factory, 'nullable', ['null', 'string'], [], false, null),
            ],
            DataTransferObject::class,
            $properties,
            NONE
        );

        $this->assertEquals($object->getProperties(), $properties);
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_not_mutable(): void
    {
        $this->expectException(ImmutableError::class);

        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            ['one' => 'One'],
            NONE
        );

        $object->__set('one', 'mutation');
    }

    /**
     * @test
     * @return void
     */
    public function mutable_flag_enables_property_mutation(): void
    {
        $newValue = 'mutation';

        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            ['one' => 'One'],
            MUTABLE
        );

        $object->__set('one', $newValue);

        $this->assertEquals($newValue, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_returns_null(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL
        );
        $data = $object->toArray();

        $this->assertArrayHasKey('one', $data);
        $this->assertNull($data['one']);
    }

    /**
     * @test
     * @return void
     */
    public function partial_can_initialise_without_required_fields(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        $this->assertNotempty($object);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_on_partial_returns_null(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        $this->assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_omitted_by_to_array(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        $this->assertArrayNotHasKey('one', $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function undefined_non_nullable_property_throws(): void
    {
        $this->expectException(UninitialisedPropertiesError::class);

        $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );
    }

    /**
     * @test
     * @return void
     */
    public function array_defaults_to_empty_array(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['array'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );


        $this->assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function empty_array_takes_precedence_over_nullable(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        $this->assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], true, 'blim')],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );


        $this->assertEquals('blim', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        $this->expectException(UninitialisedPropertiesError::class);

        $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], false, null)],
            DataTransferObject::class,
            [],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_throw_error(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $this->factory->makeWithProperties(
            [],
            DataTransferObject::class,
            ['blim' => 'blam'],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_flags(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            [],
            DataTransferObject::class,
            ['blim' => 'blam'],
            IGNORE_UNKNOWN_PROPERTIES
        );

        $this->assertEquals([], $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function partial_flags_makes_properties_nullable(): void
    {
        $data = ['one' => 1];
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['int'], [], false, null),
                'two' => new Property($this->factory, 'two', ['string'], [], false, null),
            ],
            DataTransferObject::class,
            $data,
            PARTIAL
        );

        $this->assertEquals($data, $object->toArray());
    }
}
