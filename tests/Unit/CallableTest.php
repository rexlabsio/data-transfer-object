<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Closure;
use Rexlabs\DataTransferObject\Tests\Support\ExampleDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class CallableTest extends TestCase
{
    /**
     * @test
     */
    public static function can_make_from_closure(): void
    {
        $dto = ExampleDataTransferObject::make(function ($ref) {
            return [
                $ref->first_name => 'John',
                $ref->last_name => 'Smith',
            ];
        }, PARTIAL);

        self::assertEquals('John', $dto->first_name);
        self::assertEquals('Smith', $dto->last_name);
    }

    // /**
    //  * @test
    //  * @requires PHP 8.1
    //  */
    // public function can_make_from_shorthand_closure(): void
    // {
    //     $dto = ExampleDataTransferObject::make(fn($ref) => [
    //         $ref->first_name => 'Future',
    //         $ref->last_name => 'Man',
    //     ], PARTIAL);

    //     self::assertEquals('Future', $dto->first_name);
    //     self::assertEquals('Man', $dto->last_name);
    // }

    /**
     * @test
     */
    public function can_make_from_callable(): void
    {
        $callable = Closure::fromCallable([$this, 'myTestCallable']);
        $dto = ExampleDataTransferObject::make($callable, PARTIAL);

        self::assertEquals('Bob', $dto->first_name);
        self::assertEquals('Roberts', $dto->last_name);
    }

    private function myTestCallable($ref): array
    {
        return [
            $ref->first_name => 'Bob',
            $ref->last_name => 'Roberts',
        ];
    }
}
