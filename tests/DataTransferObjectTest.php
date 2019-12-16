<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
use Rexlabs\DataTransferObject\Flags;
use Rexlabs\DataTransferObject\Property;
use Rexlabs\DataTransferObject\PropertyFactoryContract;

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
        DataTransferObject::setPropertyFactory(null);
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_set(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['string'], [], false, null),
                    'two' => new Property('two', ['string'], [], false, null),
                    'three' => new Property('three', ['string'], [], false, null),
                    'nullable' => new Property('nullable', ['null', 'string'], [], false, null),
                ]);
            }
        });

        $one = 'one';
        $two = 'two';
        $three = 'three';
        $object = DataTransferObject::make(
            [
                'one' => $one,
                'two' => $two,
                'three' => $three,
                'nullable' => null,
            ],
            Flags::NONE
        );

        $this->assertEquals($one, $object->__get('one'));
        $this->assertEquals($two, $object->__get('two'));
        $this->assertEquals($three, $object->__get('three'));
        $this->assertNull($object->__get('nullable'));
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_not_mutable(): void
    {
        $this->expectException(ImmutableError::class);

        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['null', 'string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            ['one' => 'One'],
            Flags::NONE
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
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['null', 'string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            ['one' => 'One'],
            Flags::MUTABLE
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
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['null', 'string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::NULLABLE_DEFAULT_TO_NULL
        );

        $this->assertNull($object->toArray()['one']);
    }

    /**
     * @test
     * @return void
     */
    public function partial_can_initialise_without_required_fields(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::PARTIAL
        );

        $this->assertNotempty($object);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_on_partial_returns_null(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::PARTIAL
        );

        $this->assertEquals(null, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_omitted_by_to_array(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::PARTIAL
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

        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['string'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::NULLABLE_DEFAULT_TO_NULL | Flags::ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        $this->assertEquals([], $object->toArray()['one']);
    }

    /**
     * @test
     * @return void
     */
    public function array_defaults_to_empty_array(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                   'name' => new Property('name', ['array'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::NULLABLE_DEFAULT_TO_NULL | Flags::ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        $this->assertEquals([], $object->__get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function empty_array_takes_precedence_over_nullable(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'name' => new Property('name', ['null', 'array'], [], false, null),
                ]);
            }
        });

        $object = DataTransferObject::make(
            [],
            Flags::NULLABLE_DEFAULT_TO_NULL | Flags::ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        $this->assertEquals([], $object->__get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'name' => new Property('name', ['null', 'array'], [], true, 'blim'),
                ]);
            }
        });


        $object = DataTransferObject::make(
            [],
            Flags::NULLABLE_DEFAULT_TO_NULL | Flags::ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        $this->assertEquals('blim', $object->__get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'name' => new Property('name', ['null', 'array'], [], false, null),
                ]);
            }
        });

        $this->expectException(UninitialisedPropertiesError::class);

        DataTransferObject::make(
            [],
            Flags::NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_throw_error(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([]);
            }
        });

        DataTransferObject::make(
            ['blim' => 'blam'],
            Flags::NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_flags(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([]);
            }
        });

        $object = DataTransferObject::make(
            ['blim' => 'blam'],
            Flags::IGNORE_UNKNOWN_PROPERTIES
        );

        $this->assertEquals([], $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function partial_flags_makes_properties_nullable(): void
    {
        DataTransferObject::setPropertyFactory(new class () implements PropertyFactoryContract
        {
            public function propertyTypes(string $class): Collection
            {
                return collect([
                    'one' => new Property('one', ['int'], [], false, null),
                    'two' => new Property('two', ['string'], [], false, null),
                ]);
            }
        });

        $data = ['one' => 1];
        $object = DataTransferObject::make(
            $data,
            Flags::PARTIAL
        );

        $this->assertEquals($data, $object->toArray());
    }
}
