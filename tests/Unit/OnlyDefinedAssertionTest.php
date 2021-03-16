<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\ValidButUnexpectedPropertiesDefinedTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class OnlyDefinedAssertionTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function exactly_defined_properties_passed_assertion(): void
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
                'last_name' => $faker->lastName,
            ],
            PARTIAL
        );

        $dto->assertOnlyDefined(['id', 'last_name']);

        // No exception was thrown
        self::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function unexpectedly_defined_properties_fail_assertion(): void
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
                'last_name' => $faker->lastName,
            ],
            PARTIAL
        );

        // We don't expect id to be defined
        $this->expectException(ValidButUnexpectedPropertiesDefinedTypeError::class);
        $dto->assertOnlyDefined(['id', 'last_name']);
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
                'id' => $faker->uuid,
            ],
            PARTIAL
        );

        $dto->assertOnlyDefined('id');

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

        $dto->assertOnlyDefined(['last_name']);
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

        $dto->assertOnlyDefined(['flim']);
    }
}
