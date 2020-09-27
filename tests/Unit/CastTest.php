<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use ArrayObject;
use Faker\Factory as Faker;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject2;
use Rexlabs\DataTransferObject\Tests\TestCase;
use Rexlabs\DataTransferObject\Type\PropertyCast;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class CastTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function implicit_casts_numeric_string_to_int(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'age' => ['int'],
            ]
        );

        $dto = TestDataTransferObject::make(
            [
                'age' => '14',
            ]
        );

        $dto->assertDefined('age');
        self::assertIsInt($dto->__get('age'));
    }

    /**
     * @test
     * @return void
     */
    public function can_make_with_nested_objects(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [

                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
                'parent' => ['null', TestDataTransferObject::class],
                'partner' => ['null', TestDataTransferObject::class],
                'siblings' => ['null', TestDataTransferObject::class . '[]'],
            ]
        );

        $object = TestDataTransferObject::make(
            [
                'id' => 'test_id',
                'first_name' => 'Joe',
                'last_name' => 'Dirt',
                'parent' => [
                    'id' => 'test_id_2',
                    'first_name' => 'Geoff',
                    'last_name' => 'Dirt',
                ],
                'partner' => [
                    'id' => 'test_id_3',
                    'first_name' => 'Jill',
                    'last_name' => 'Dirt',
                ],
                'siblings' => [
                    [
                        'id' => 'test_id_4',
                        'first_name' => 'Dave',
                        'last_name' => 'Dirt',
                        'parent' => [
                            'id' => 'test_id_6',
                            'first_name' => 'Fred',
                            'last_name' => 'Dirt',
                        ],
                    ],
                    [
                        'id' => 'test_id_5',
                        'first_name' => 'Carl',
                        'last_name' => 'Dirt',
                    ],
                ],
            ],
            PARTIAL
        );
        $parent = $object->__get('parent');
        $partner = $object->__get('partner');
        $siblings = $object->__get('siblings');

        self::assertEquals('test_id', $object->__get('id'));
        self::assertEquals('Joe', $object->__get('first_name'));
        self::assertEquals('Dirt', $object->__get('last_name'));

        self::assertInstanceOf(TestDataTransferObject::class, $parent);
        self::assertEquals('test_id_2', $parent->__get('id'));
        self::assertEquals('Geoff', $parent->__get('first_name'));
        self::assertEquals('Dirt', $parent->__get('last_name'));

        self::assertInstanceOf(TestDataTransferObject::class, $partner);
        self::assertEquals('test_id_3', $partner->__get('id'));
        self::assertEquals('Jill', $partner->__get('first_name'));
        self::assertEquals('Dirt', $partner->__get('last_name'));

        self::assertCount(2, $siblings);
        foreach ($siblings as $sibling) {
            self::assertInstanceOf(TestDataTransferObject::class, $sibling);
        }

        self::assertInstanceOf(TestDataTransferObject::class, $siblings[0]->__get('parent'));
        $siblingParent = $siblings[0]->__get('parent');
        self::assertEquals('test_id_6', $siblingParent->__get('id'));
        self::assertEquals('Fred', $siblingParent->__get('first_name'));
    }

    /**
     * @test
     * @return void
     */
    public function can_cast_sequential_array(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'items' => [TestDataTransferObject2::class . '[]'],
            ]
        );

        $faker = Faker::create();
        $name1 = $faker->name;
        $name2 = $faker->name;
        $name3 = $faker->name;

        $dto = TestDataTransferObject::make([
            'items' => [
                ['name' => $name1],
                ['name' => $name2],
                ['name' => $name3],
            ],
        ]);

        self::assertInstanceOf(TestDataTransferObject::class, $dto);
        self::assertIsArray($dto->__get('items'));
        self::assertArrayHasKey(0, $dto->__get('items'));
        self::assertArrayHasKey(1, $dto->__get('items'));
        self::assertArrayHasKey(2, $dto->__get('items'));
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[0]);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[1]);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[2]);
        self::assertEquals($name1, $dto->__get('items')[0]->__get('name'));
        self::assertEquals($name2, $dto->__get('items')[1]->__get('name'));
        self::assertEquals($name3, $dto->__get('items')[2]->__get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function can_cast_out_of_sequence_array(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'items' => [TestDataTransferObject2::class . '[]'],
            ]
        );

        $faker = Faker::create();
        $name1 = $faker->name;
        $name2 = $faker->name;
        $name3 = $faker->name;

        $dto = TestDataTransferObject::make([
            'items' => [
                3 => ['name' => $name1],
                123 => ['name' => $name2],
                10 => ['name' => $name3],
            ],
        ]);

        self::assertInstanceOf(TestDataTransferObject::class, $dto);
        self::assertIsArray($dto->__get('items'));
        self::assertArrayHasKey(3, $dto->__get('items'));
        self::assertArrayHasKey(123, $dto->__get('items'));
        self::assertArrayHasKey(10, $dto->__get('items'));
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[3]);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[123]);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[10]);
        self::assertEquals($name1, $dto->__get('items')[3]->__get('name'));
        self::assertEquals($name2, $dto->__get('items')[123]->__get('name'));
        self::assertEquals($name3, $dto->__get('items')[10]->__get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function can_cast_associative_array(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'items' => [TestDataTransferObject2::class . '[]'],
            ]
        );

        $faker = Faker::create();
        $name1 = $faker->name;
        $name2 = $faker->name;
        $name3 = $faker->name;

        $dto = TestDataTransferObject::make([
            'items' => [
                'one' => ['name' => $name1],
                'two' => ['name' => $name2],
                'three' => ['name' => $name3],
            ],
        ]);

        self::assertInstanceOf(TestDataTransferObject::class, $dto);
        self::assertIsArray($dto->__get('items'));
        self::assertArrayHasKey('one', $dto->__get('items'));
        self::assertArrayHasKey('two', $dto->__get('items'));
        self::assertArrayHasKey('three', $dto->__get('items'));
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')['one']);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')['two']);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')['three']);
        self::assertEquals($name1, $dto->__get('items')['one']->__get('name'));
        self::assertEquals($name2, $dto->__get('items')['two']->__get('name'));
        self::assertEquals($name3, $dto->__get('items')['three']->__get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function can_to_data_assoc_array(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'items' => [TestDataTransferObject2::class . '[]'],
            ]
        );

        $faker = Faker::create();
        $name1 = $faker->name;
        $name2 = $faker->name;
        $name3 = $faker->name;


        $dto = TestDataTransferObject::make([
            'items' => [
                // Make manually to avoid running the casting code
                // This test is for toData not toType
                'one' => TestDataTransferObject2::make(['name' => $name1]),
                'two' => TestDataTransferObject2::make(['name' => $name2]),
                'three' => TestDataTransferObject2::make(['name' => $name3]),
            ]
        ]);

        $expected = [
            'items' => [
                'one' => ['name' => $name1],
                'two' => ['name' => $name2],
                'three' => ['name' => $name3],
            ],
        ];

        self::assertEquals($expected, $dto->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function dto_or_dto_array_type_can_cast_if_not_assoc(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                // Items can either be a single DTO or an array of DTOs
                'items' => [
                    TestDataTransferObject2::class,
                    TestDataTransferObject2::class . '[]'
                ],
            ]
        );

        $faker = Faker::create();

        $dto = TestDataTransferObject::make([
            'items' => [
                5 => ['name' => $faker->firstName],
                10 => ['name' => $faker->firstName],
            ],
        ]);

        self::assertInstanceOf(TestDataTransferObject::class, $dto);
        self::assertIsArray($dto->__get('items'));
        self::assertArrayHasKey(5, $dto->__get('items'));
        self::assertArrayHasKey(10, $dto->__get('items'));
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[5]);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('items')[10]);
    }

    /**
     * @test
     * @return void
     */
    public function limitation_demo_dto_or_dto_array_type_fails_if_assoc(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                // Items can either be a single DTO or an array of DTOs
                'items' => [
                    TestDataTransferObject2::class,
                    TestDataTransferObject2::class . '[]'
                ],
            ]
        );

        $faker = Faker::create();

        // First all array items are cast to TestDataTransferObject2
        // Then because the collection is assoc the DTO cast thinks it can cast
        // DTO cast fails because it doesn't recognise the array keys as valid
        // props
        // Suggestion is to not have DTO|DTO[] types, or to override with a
        // custom cast that looks at the keys first
        $this->expectException(UnknownPropertiesTypeError::class);
        TestDataTransferObject::make([
            'items' => [
                'one' => ['name' => $faker->firstName],
                'two' => ['name' => $faker->firstName],
            ],
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function property_cast_runs_before_default_factory_cast(): void
    {
        // Cast an array to a string before the default DataTransferObject cast
        $specialCast = new class () implements PropertyCast {
            public function canCastType(string $type): bool
            {
                return $type === 'string';
            }

            public function toType(string $name, $data, string $type, int $flags = NONE)
            {
                if (!isset($data['first_name'], $data['last_name'])) {
                    return $data;
                }

                return $data['first_name'] . ' ' . $data['last_name'];
            }

            public function toData(string $name, $property, int $flags = NONE)
            {
                if (!is_string($property)) {
                    return $property;
                }

                $parts = explode(' ', $property);
                return [
                    'first_name' => $parts[0],
                    'last_name' => $parts[1],
                ];
            }
        };

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
                // Cast parent data to TestDataTransferObject
                'parent' => ['null', TestDataTransferObject::class],
                // Cast to a string before the default DataTransferObject cast
                'special_parent' => ['null', 'string', TestDataTransferObject::class],
            ],
            [],
            // Normally this would cast to TestDataTransferObject but
            // it should use the override cast instead where possible
            ['special_parent' => $specialCast]
        );

        $faker = Faker::create();

        $dto = TestDataTransferObject::make(
            [
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'parent' => [
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'parent' => null,
                    'special_parent' => null,
                ],
                'special_parent' => [
                    'first_name' => 'Joe',
                    'last_name' => 'Dirt',
                    'parent' => null,
                    'special_parent' => null,
                ],
            ]
        );

        self::assertInstanceOf(TestDataTransferObject::class, $dto->__get('parent'));
        self::assertIsString($dto->__get('special_parent'));
        self::assertEquals('Joe Dirt', $dto->__get('special_parent'));
    }

    /**
     * @test
     * @return void
     */
    public function can_cast_array_items_then_combine_into_collection_type(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
            ]
        );

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'users' => [ArrayObject::class, TestDataTransferObject2::class . '[]'],
            ]
        );

        $faker = Faker::create();

        $firstName1 = $faker->firstName;
        $lastName1 = $faker->lastName;
        $firstName2 = $faker->firstName;
        $lastName2 = $faker->lastName;

        $dto = TestDataTransferObject::make([
            'users' => [
                [
                    'first_name' => $firstName1,
                    'last_name' => $lastName1,
                ],
                [
                    'first_name' => $firstName2,
                    'last_name' => $lastName2,
                ],
            ],
        ]);

        self::assertInstanceOf(ArrayObject::class, $dto->__get('users'));
        self::assertCount(2, $dto->__get('users'));
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('users')[0]);
        self::assertInstanceOf(TestDataTransferObject2::class, $dto->__get('users')[1]);
        self::assertEquals($firstName1, $dto->__get('users')[0]->__get('first_name'));
        self::assertEquals($lastName1, $dto->__get('users')[0]->__get('last_name'));
        self::assertEquals($firstName2, $dto->__get('users')[1]->__get('first_name'));
        self::assertEquals($lastName2, $dto->__get('users')[1]->__get('last_name'));
    }

    /**
     * @test
     * @return void
     */
    public function can_to_array_collection_type_then_to_array_each_array_item(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject2::class,
            ['name' => ['string']]
        );

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            ['users' => [ArrayObject::class, TestDataTransferObject2::class . '[]']]
        );

        $faker = Faker::create();
        $name1 = $faker->name;
        $name2 = $faker->name;

        $dto = TestDataTransferObject::make([
            'users' => new ArrayObject([
                'one' => TestDataTransferObject2::make(['name' => $name1]),
                'two' => TestDataTransferObject2::make(['name' => $name2]),
            ]),
        ]);

        $expected = [
            'users' => [
                'one' => ['name' => $name1],
                'two' => ['name' => $name2],
            ]
        ];

        self::assertEquals($expected, $dto->toArray());
    }
}
