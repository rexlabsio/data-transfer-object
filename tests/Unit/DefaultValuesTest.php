<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Factory;

use const Rexlabs\DataTransferObject\DEFAULTS;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class DefaultValuesTest extends TestCase
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
     *
     * @return void
     */
    public function defaults_not_set_without_flag(): void
    {
        // Missing two parameters that can have defaults
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        $this->expectException(UndefinedPropertiesTypeError::class);

        // Missing properties have default values
        $this->factory->make(
            DataTransferObject::class,
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
            $properties,
            NONE
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function defaults_not_set_without_flag_with_partial(): void
    {
        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = $this->factory->make(
            DataTransferObject::class,
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
            $properties,
            PARTIAL
        );

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
        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = $this->factory->make(
            DataTransferObject::class,
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
            $properties,
            PARTIAL
        );

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
        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = $this->factory->make(
            DataTransferObject::class,
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
            $properties,
            PARTIAL | DEFAULTS
        );

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
        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = $this->factory->make(
            DataTransferObject::class,
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
            $properties,
            DEFAULTS
        );

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
        // Missing two parameters
        $properties = [
            'one' => 'one',
            'two' => 'two',
        ];

        // Missing properties have default values
        $dto = $this->factory->make(
            DataTransferObject::class,
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
            $properties,
            DEFAULTS
        );

        self::assertTrue($dto->isDefined('three'));
        self::assertTrue($dto->isDefined('four'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_with_defaults_returns_null(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['null', 'string'])],
            [],
            DEFAULTS
        );
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
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['null', 'string'])],
            [],
            PARTIAL
        );

        self::assertArrayNotHasKey('one', $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function undefined_non_nullable_property_throws(): void
    {
        $this->expectException(UndefinedPropertiesTypeError::class);

        $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['string'])],
            [],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function array_defaults_to_empty_array_with_defaults(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['array'])],
            [],
            DEFAULTS
        );

        self::assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function bool_defaults_to_false_with_defaults(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['bool'])],
            [],
            DEFAULTS
        );


        self::assertEquals(false, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_takes_precedence_over_empty_array(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['null', 'array'])],
            [],
            DEFAULTS
        );

        self::assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['null', 'array'], ['one' => 'blim'])],
            [],
            DEFAULTS
        );


        self::assertEquals('blim', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        $this->expectException(UndefinedPropertiesTypeError::class);

        $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['null', 'array', 'bool'])],
            [],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function undefined_property_reverts_to_default(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['blim' => $this->factory->makePropertyType('blim', ['string'], ['blim' => 'blam'])],
            [],
            DEFAULTS
        );

        self::assertEquals('blam', $object->__get('blim'));
    }
}
