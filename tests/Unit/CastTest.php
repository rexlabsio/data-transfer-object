<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory as Faker;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;
use Rexlabs\DataTransferObject\Type\PropertyCast;

use const Rexlabs\DataTransferObject\NONE;

class CastTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function casts_numeric_string_to_int(): void
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
    public function property_cast_overrides_default_factory_cast(): void
    {
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
                // Cast to a string instead using the override special cast
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
}
