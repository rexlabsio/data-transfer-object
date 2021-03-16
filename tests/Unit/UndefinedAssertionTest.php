<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class UndefinedAssertionTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function undefined_properties_passed_assertion(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]
        );

        $faker = Factory::create();

        $dto = TestDataTransferObject::make(
            [
                'first_name' => $faker->firstName,
            ],
            PARTIAL
        );

        $dto->assertUndefined(['id', 'last_name']);

        // No exception was thrown
        self::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_test_single_string_prop(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]
        );

        $faker = Factory::create();
        $dto = TestDataTransferObject::make(
            [
                'first_name' => $faker->firstName,
            ],
            PARTIAL
        );

        $dto->assertUndefined('id');

        // No exception was thrown
        self::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function undefined_properties_fail_assertion(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]
        );

        $faker = Factory::create();
        $dto = TestDataTransferObject::make(
            [
                'id' => $faker->uuid,
                'first_name' => $faker->firstName,
            ],
            PARTIAL
        );

        $this->expectException(UndefinedPropertiesTypeError::class);

        $dto->assertDefined(['last_name']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function unknown_properties_fail_before_assertion(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]
        );

        $faker = Factory::create();
        $dto = TestDataTransferObject::make(
            [
                'id' => $faker->uuid,
                'first_name' => $faker->firstName,
            ],
            PARTIAL
        );

        $this->expectException(UnknownPropertiesTypeError::class);

        $dto->assertUndefined(['flim']);
    }
}
