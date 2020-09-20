<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingDto;

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
        $faker = Factory::create();
        $dto = TestingDto::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ]);

        $remade = $dto->remake([
            'last_name' => 'Dirt',
        ]);

        self::assertEquals($dto->id, $remade->id);
        self::assertEquals($dto->first_name, $remade->first_name);
        self::assertEquals('Dirt', $remade->last_name);
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_remake_only_props(): void
    {
        $faker = Factory::create();
        $dto = TestingDto::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ]);

        $remade = $dto->remakeOnly(['id', 'last_name'], [], PARTIAL);

        self::assertEquals($dto->id, $remade->id);
        self::assertFalse($remade->isDefined('first_name'));
        self::assertEquals($dto->last_name, $remade->last_name);
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_remake_except_props(): void
    {
        $faker = Factory::create();
        $dto = TestingDto::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ]);

        $remade = $dto->remakeExcept(['last_name'], [], PARTIAL);

        self::assertEquals($dto->id, $remade->id);
        self::assertEquals($dto->first_name, $remade->first_name);
        self::assertFalse($remade->isDefined('last_name'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function remake_rejects_unknown_only_names(): void
    {
        $faker = Factory::create();
        $dto = TestingDto::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ]);

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
        $faker = Factory::create();
        $dto = TestingDto::make([
            'id' => $faker->uuid,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ]);

        $this->expectException(UnknownPropertiesTypeError::class);

        $dto->remakeExcept(['flam'], [], PARTIAL);
    }
}
