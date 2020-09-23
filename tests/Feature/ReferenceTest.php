<?php

namespace Rexlabs\DataTransferObject\Tests\Feature;

use Faker\Factory as Faker;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\ExampleDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class ReferenceTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function static_ref_returns_valid_strings(): void
    {
        $ref = ExampleDataTransferObject::ref();

        $firstNamePropertyName = $ref->first_name;
        $siblingsPropertyName = $ref->siblings;

        self::assertIsString($firstNamePropertyName);
        self::assertEquals('first_name', $firstNamePropertyName);
        self::assertIsString($siblingsPropertyName);
        self::assertEquals('siblings', $siblingsPropertyName);
    }

    /**
     * @test
     * @return void
     */
    public function instance_ref_returns_valid_strings(): void
    {
        $dto = ExampleDataTransferObject::make([], PARTIAL);
        $ref = $dto::ref();

        $firstNamePropertyName = $ref->first_name;
        $siblingsPropertyName = $ref->siblings;

        self::assertIsString($firstNamePropertyName);
        self::assertEquals('first_name', $firstNamePropertyName);
        self::assertIsString($siblingsPropertyName);
        self::assertEquals('siblings', $siblingsPropertyName);
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_ref_throws_unknown(): void
    {
        $dto = ExampleDataTransferObject::make([], PARTIAL);

        $isDefined = $dto->refIsDefined();

        $this->expectException(UnknownPropertiesTypeError::class);
        $isDefined->__get('not a real property name');
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_returns_valid_bool(): void
    {
        $faker = Faker::create();

        $dto = ExampleDataTransferObject::make([
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ], PARTIAL);

        $isDefined = $dto->refIsDefined();
        $firstNameDefined = $isDefined->first_name;

        self::assertIsBool($firstNameDefined);
        self::assertTrue($firstNameDefined);

        self::assertTrue($isDefined->last_name);
        self::assertFalse($isDefined->id);
    }
}
