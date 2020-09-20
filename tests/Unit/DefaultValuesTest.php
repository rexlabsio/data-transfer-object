<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\DEFAULTS;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class DefaultValuesTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function defaults_not_set_without_flag(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'four' => ['null', 'string'],
                ],
                [
                    'three' => 'default_value',
                    'four' => '',
                ]
            ),
            NONE
        ));

        // Missing two parameters that can have defaults
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        $this->expectException(UndefinedPropertiesTypeError::class);

        // Missing properties have default values
        $object = TestDataTransferObject::make($properties, NONE);
        self::assertEquals('default_value', $object->__get('three'));
        self::assertEquals('', $object->__get('four'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_not_set_without_flag_with_partial(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'four' => ['null', 'string'],
                ],
                [
                    'three' => 'default_value',
                    'four' => '',
                ]
            ),
            NONE
        ));

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, PARTIAL);

        self::assertFalse($dto->isDefined('three'));
        self::assertFalse($dto->isDefined('four'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function access_to_undefined_on_partial_throws(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'four' => ['null', 'string'],
                ],
                [
                    'three' => 'default_value',
                    'four' => '',
                ]
            ),
            NONE
        ));

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, PARTIAL);

        $this->expectException(UndefinedPropertiesTypeError::class);

        $dto->__get('three');
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_set_with_flag_with_partial(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'four' => ['null', 'string'],
                ],
                [
                    'three' => 'default_value',
                    'four' => '',
                ]
            ),
            NONE
        ));

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, PARTIAL | DEFAULTS);

        self::assertTrue($dto->isDefined('three'));
        self::assertTrue($dto->isDefined('four'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_set_with_flag(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'four' => ['null', 'string'],
                ],
                [
                    'three' => 'default_value',
                    'four' => '',
                ]
            ),
            NONE
        ));

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, DEFAULTS);

        self::assertEquals('default_value', $dto->__get('three'));
        self::assertEquals('', $dto->__get('four'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_count_as_defined(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'four' => ['null', 'string'],
                ],
                [
                    'three' => 'default_value',
                    'four' => '',
                ]
            ),
            NONE
        ));

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        $dto = TestDataTransferObject::make($properties, DEFAULTS);

        self::assertTrue($dto->isDefined('three'));
        self::assertTrue($dto->isDefined('four'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_with_defaults_returns_null(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'string']]),
            NONE
        ));

        $object = TestDataTransferObject::make([], DEFAULTS);
        $data = $object->toArray();

        self::assertArrayHasKey('one', $data);
        self::assertNull($data['one']);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_omitted_by_to_array(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'string']]),
            NONE
        ));

        $object = TestDataTransferObject::make([], PARTIAL);

        self::assertArrayNotHasKey('one', $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function undefined_non_nullable_property_throws(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['string']]),
            NONE
        ));

        $this->expectException(UndefinedPropertiesTypeError::class);

        TestDataTransferObject::make([], NONE);
    }

    /**
     * @test
     * @return void
     */
    public function array_defaults_to_empty_array_with_defaults(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['array']]),
            NONE
        ));

        $object = TestDataTransferObject::make([], DEFAULTS);

        self::assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function bool_defaults_to_false_with_defaults(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['bool']]),
            NONE
        ));

        $object = TestDataTransferObject::make([], DEFAULTS);

        self::assertEquals(false, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_takes_precedence_over_empty_array(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'array']]),
            NONE
        ));

        $object = TestDataTransferObject::make([], DEFAULTS);

        self::assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['string']], ['one' => 'blim']),
            NONE
        ));

        $object = TestDataTransferObject::make([], DEFAULTS);

        self::assertEquals('blim', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'array', 'bool']]),
            NONE
        ));

        $this->expectException(UndefinedPropertiesTypeError::class);

        TestDataTransferObject::make([], NONE);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_property_reverts_to_default(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['blim' => ['string']], ['blim' => 'blam']),
            NONE
        ));

        $object = TestDataTransferObject::make([], DEFAULTS);

        self::assertEquals('blam', $object->__get('blim'));
    }
}
