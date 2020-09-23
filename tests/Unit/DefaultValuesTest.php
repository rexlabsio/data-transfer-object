<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;
use const Rexlabs\DataTransferObject\WITH_DEFAULTS;

class DefaultValuesTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function defaults_not_set_without_flag(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

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
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

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
    public function defaults_not_set_on_to_array_without_flag_with_partial(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, PARTIAL);
        $data = $dto->toArray();

        self::assertArrayNotHasKey('three', $data);
        self::assertArrayNotHasKey('four', $data);
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_set_on_to_array_with_defaults_without_flag_with_partial(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'one' => ['string'],
                'two' => ['string'],
                'three' => ['string'],
                'four' => ['null', 'string'],
                'parent' => [TestDataTransferObject::class],
            ],
            [
                'three' => 'default_value',
                'four' => '',
            ]
        );

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
            'parent' => [
                'one' => 'one',
                'two' => 'two',
            ],
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, PARTIAL);
        $data = $dto->toArrayWithDefaults();

        self::assertArrayHasKey('three', $data);
        self::assertArrayHasKey('four', $data);
        self::assertArrayHasKey('parent', $data);

        $parent = $data['parent'];
        self::assertArrayHasKey('three', $parent);
        self::assertArrayHasKey('four', $parent);
    }

    /**
     * @test
     *
     * @return void
     */
    public function access_to_undefined_on_partial_throws(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

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
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

        // Missing two parameters
        $properties = [
            'one' => 'one',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, PARTIAL | WITH_DEFAULTS);

        self::assertTrue($dto->isDefined('one'), 'Property one set with params');
        self::assertFalse($dto->isDefined('two'), 'Property two has no default value and should remain undefined');
        self::assertTrue($dto->isDefined('three'), 'Property three should be defined with default');
        self::assertTrue($dto->isDefined('four'), 'Property four should be defined with default');
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_set_with_flag(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = TestDataTransferObject::make($properties, WITH_DEFAULTS);

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
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
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
        );

        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        $dto = TestDataTransferObject::make($properties, WITH_DEFAULTS);

        self::assertTrue($dto->isDefined('three'));
        self::assertTrue($dto->isDefined('four'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_with_defaults_returns_null(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['null', 'string']]
        );

        $object = TestDataTransferObject::make([], WITH_DEFAULTS);
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
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['null', 'string']]
        );

        $object = TestDataTransferObject::make([], PARTIAL);

        self::assertArrayNotHasKey('one', $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function undefined_non_nullable_property_throws(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['string']]
        );

        $this->expectException(UndefinedPropertiesTypeError::class);

        TestDataTransferObject::make([], NONE);
    }

    /**
     * @test
     * @return void
     */
    public function array_defaults_to_empty_array_with_defaults(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['array']]
        );

        $object = TestDataTransferObject::make([], WITH_DEFAULTS);

        self::assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function bool_defaults_to_false_with_defaults(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['bool']]
        );

        $object = TestDataTransferObject::make([], WITH_DEFAULTS);

        self::assertEquals(false, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_takes_precedence_over_empty_array(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['null', 'array']]
        );

        $object = TestDataTransferObject::make([], WITH_DEFAULTS);

        self::assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['string']],
            ['one' => 'blim']
        );

        $object = TestDataTransferObject::make([], WITH_DEFAULTS);

        self::assertEquals('blim', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['one' => ['null', 'array', 'bool']]
        );

        $this->expectException(UndefinedPropertiesTypeError::class);

        TestDataTransferObject::make([], NONE);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_property_reverts_to_default(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['blim' => ['string']],
            ['blim' => 'blam']
        );

        $object = TestDataTransferObject::make([], WITH_DEFAULTS);

        self::assertEquals('blam', $object->__get('blim'));
    }
}
