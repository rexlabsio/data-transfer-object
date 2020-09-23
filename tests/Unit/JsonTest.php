<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory as Faker;
use InvalidArgumentException;
use LogicException;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class JsonTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function can_make_from_json(): void
    {
        $json = <<<JSON
{"first_name": "Joe", "last_name": "Dirt"}
JSON;

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
            ]
        );

        $dto = TestDataTransferObject::makeFromJson($json);

        self::assertEquals('Joe', $dto->__get('first_name'));
        self::assertEquals('Dirt', $dto->__get('last_name'));
    }

    /**
     * @test
     * @return void
     */
    public function invalid_json_on_make_throws_invalid_argument(): void
    {
        $json = 'I am not a valid json string';

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        TestDataTransferObject::makeFromJson($json);
    }

    /**
     * @test
     * @return void
     */
    public function can_serialise_to_json(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
            ]
        );

        $faker = Faker::create();
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;

        $json = TestDataTransferObject
            ::make(
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]
            )
            ->toJson();

        self::assertStringContainsString($firstName, $json);
        self::assertStringContainsString($lastName, $json);
    }

    /**
     * @test
     * @return void
     */
    public function can_serialise_to_json_with_defaults(): void
    {
        $faker = Faker::create();
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]
        );

        $dto = TestDataTransferObject::make([], PARTIAL);

        self::assertFalse($dto->isDefined('first_name'), 'Partial should not have set default values');
        self::assertFalse($dto->isDefined('last_name'), 'Partial should not have set default values');

        $json = $dto->toJsonWithDefaults();

        self::assertStringContainsString($firstName, $json);
        self::assertStringContainsString($lastName, $json);
    }

    /**
     * @test
     * @return void
     */
    public function to_json_with_unsupported_property_types_throws_logic_exception(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
                'file' => ['resource'],
            ]
        );

        $faker = Faker::create();
        $firstName = $faker->lastName;
        $lastName = $faker->lastName;
        $fileResourceHandle = fopen(__FILE__, 'rb');

        $dto = TestDataTransferObject::make(
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'file' => $fileResourceHandle,
            ]
        );

        self::assertIsResource($dto->__get('file'));

        $this->expectException(LogicException::class);
        $dto->toJson();
    }
}
