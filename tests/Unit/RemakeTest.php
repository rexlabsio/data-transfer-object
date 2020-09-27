<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class RemakeTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function can_remake_with_overrides(): void
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
            ]
        );

        $remade = $dto->remake(
            [
                'last_name' => 'Dirt',
            ]
        );

        self::assertEquals($dto->__get('id'), $remade->__get('id'));
        self::assertEquals($dto->__get('first_name'), $remade->__get('first_name'));
        self::assertEquals('Dirt', $remade->__get('last_name'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_remake_only_props(): void
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
            ]
        );

        $remade = $dto->remakeOnly(['id', 'last_name'], [], PARTIAL);

        self::assertEquals($dto->__get('id'), $remade->__get('id'));
        self::assertFalse($remade->isDefined('first_name'));
        self::assertEquals($dto->__get('last_name'), $remade->__get('last_name'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_remake_except_props(): void
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
            ]
        );

        $remade = $dto->remakeExcept(['last_name'], [], PARTIAL);

        self::assertEquals($dto->__get('id'), $remade->__get('id'));
        self::assertEquals($dto->__get('first_name'), $remade->__get('first_name'));
        self::assertFalse($remade->isDefined('last_name'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function remake_rejects_unknown_only_names(): void
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
            ]
        );

        $this->expectException(UnknownPropertiesTypeError::class);

        $dto->remakeExcept(['flim'], [], PARTIAL);
    }

    /**
     * @test
     *
     * @return void
     */
    public function remake_rejects_unknown_except_names(): void
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
            ]
        );

        $this->expectException(UnknownPropertiesTypeError::class);

        $dto->remakeExcept(['flam'], [], PARTIAL);
    }
}
