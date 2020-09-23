<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use ArrayObject;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class IssetIsDefinedTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function defined_property_returns_isset_true(): void
    {
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['bool'],
                ]
            )
            ->propertyTypes;

        $object = new TestDataTransferObject(
            $propertyTypes,
            ['blim' => true],
            [],
            NONE
        );

        self::assertTrue(isset($object->blim));
    }

    /**
     * See php isset documentation
     *
     * @test
     * @return void
     */
    public function defined_null_value_property_returns_isset_false(): void
    {
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                ]
            )
            ->propertyTypes;

        $object = new TestDataTransferObject(
            $propertyTypes,
            ['blim' => null],
            [],
            NONE
        );

        self::assertFalse(isset($object->blim));
    }

    /**
     * @test
     * @return void
     */
    public function defined_to_anything_properties_return_is_defined_true(): void
    {
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                    'blam' => ['bool'],
                ]
            )
            ->propertyTypes;

        $object = new TestDataTransferObject(
            $propertyTypes,
            [
                'blim' => null,
                'blam' => true,
            ],
            [],
            NONE
        );

        self::assertTrue($object->isDefined('blim'));
        self::assertTrue($object->isDefined('blam'));
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_supports_dot_notation_for_nested_properties(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'parent' => ['null', TestDataTransferObject::class],
                'flim' => ['null', 'string'],
            ]
        );

        $dto = TestDataTransferObject::make(
            [
                'parent' => [
                    'parent' => [
                        'parent' => [
                            'flim' => 'flam',
                        ],
                    ],
                ],
            ],
            PARTIAL
        );

        self::assertTrue($dto->isDefined('parent.parent.parent.flim'));
        self::assertInstanceOf(TestDataTransferObject::class, $dto->__get('parent')->__get('parent')->__get('parent'));
        self::assertIsString($dto->__get('parent')->__get('parent')->__get('parent')->__get('flim'));
        self::assertFalse($dto->isDefined('parent.parent.parent.parent'));
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_supports_dot_notation_for_nested_arrays(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'parent' => ['array'],
            ]
        );

        $dto = TestDataTransferObject::make(
            [
                'parent' => [
                    'parent' => [
                        'parent' => [
                            'flim' => 'flam',
                        ],
                    ],
                ],
            ]
        );

        self::assertTrue($dto->isDefined('parent.parent.parent.flim'));
        self::assertIsArray($dto->__get('parent')['parent']['parent']);
        self::assertIsString($dto->__get('parent')['parent']['parent']['flim']);
        self::assertFalse($dto->isDefined('parent.parent.parent.parent'));
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_supports_dot_notation_for_nested_objects(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'parent' => ['stdClass'],
            ]
        );

        $dto = TestDataTransferObject::make(
            [
                'parent' => (object)[
                    'parent' => (object)[
                        'parent' => (object)[
                            'flim' => 'flam',
                        ],
                    ],
                ],
            ]
        );

        self::assertTrue($dto->isDefined('parent.parent.parent.flim'));
        self::assertIsObject($dto->__get('parent')->parent->parent);
        self::assertIsString($dto->__get('parent')->parent->parent->flim);
        self::assertFalse($dto->isDefined('parent.parent.parent.parent'));
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_supports_dot_notation_for_nested_array_access_objects(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'parent' => ['ArrayAccess'],
            ]
        );

        $dto = TestDataTransferObject::make(
            [
                'parent' => new ArrayObject(
                    [
                        'parent' => new ArrayObject(
                            [
                                'parent' => new ArrayObject(
                                    [
                                        'flim' => 'flam',
                                    ]
                                ),
                            ]
                        ),
                    ]
                ),
            ]
        );

        self::assertTrue($dto->isDefined('parent.parent.parent.flim'));
        self::assertIsObject($dto->__get('parent')['parent']['parent']);
        self::assertIsString($dto->__get('parent')['parent']['parent']['flim']);
        self::assertFalse($dto->isDefined('parent.parent.parent.parent'));
    }

    /**
     * @test
     * @return void
     */
    public function nested_unknown_properties_show_path(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'parent' => ['null', TestDataTransferObject::class],
                'flim' => ['null', 'string'],
            ]
        );

        $dto = TestDataTransferObject::make(
            [
                'parent' => [
                    'parent' => [
                        'parent' => [
                            'flim' => 'flam',
                        ],
                    ],
                ],
            ],
            PARTIAL
        );

        self::assertTrue($dto->isDefined('parent.parent.parent'));

        try {
            $dto->isDefined('parent.parent.parent.braawwwwwwwwp.beep');
            self::fail('Expected have thrown unknown property exception');
            return;
        } catch (UnknownPropertiesTypeError $e) {
            self::assertRegExp('/\\n.*\bparent.parent.parent.braawwwwwwwwp\b$/', $e->getMessage());
        }
    }

    /**
     * @test
     * @return void
     */
    public function undefined_property_returns_is_defined_false(): void
    {
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                ]
            )
            ->propertyTypes;

        $object = new TestDataTransferObject(
            $propertyTypes,
            [],
            [],
            NONE
        );

        self::assertFalse($object->isDefined('blim'));
    }

    /**
     * @test
     * @return void
     */
    public function unknown_property_is_defined_throws(): void
    {
        $this->expectException(UnknownPropertiesTypeError::class);

        $propertyTypes = $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'blim' => ['null'],
            ]
        )
            ->propertyTypes;

        $object = new TestDataTransferObject(
            $propertyTypes,
            [],
            [],
            NONE
        );

        self::assertFalse($object->isDefined('blam'));
    }
}
