<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory;
use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class AssertionTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function defined_properties_passed_assertion(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]),
            NONE
        ));

        $faker = Factory::create();

        $dto = TestDataTransferObject::make([
            'id' => $faker->uuid,
            'last_name' => $faker->lastName,
        ], PARTIAL);

        $dto->assertDefined(['id', 'last_name']);

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
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]),
            NONE
        ));

        $faker = Factory::create();
        $dto = TestDataTransferObject::make([
            'id' => $faker->uuid,
            'last_name' => $faker->lastName,
        ], PARTIAL);

        $dto->assertDefined('id');

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
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]),
            NONE
        ));

        $faker = Factory::create();
        $dto = TestDataTransferObject::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
        ], PARTIAL);

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
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
            ]),
            NONE
        ));

        $faker = Factory::create();
        $dto = TestDataTransferObject::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
        ], PARTIAL);

        $this->expectException(UnknownPropertiesTypeError::class);

        $dto->assertDefined(['flim']);
    }
}
