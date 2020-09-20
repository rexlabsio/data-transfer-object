<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;

class ValidTypeTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function valid_type_on_make_succeeds(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'flim' => ['null', 'string'],
            ]),
            NONE
        ));

        $dto = TestDataTransferObject::make(['flim' => 'flam']);
        self::assertEquals('flam', $dto->__get('flim'));
    }

    /**
     * @test
     * @return void
     */
    public function invalid_type_on_make_throws(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'flim' => ['null', 'string'],
            ]),
            NONE
        ));

        $this->expectException(InvalidTypeError::class);

        TestDataTransferObject::make(['flim' => false]);
    }

    /**
     * @test
     * @return void
     */
    public function valid_type_on_set_succeeds(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'flim' => ['null', 'string'],
            ]),
            NONE
        ));

        $dto = TestDataTransferObject::make(['flim' => null], MUTABLE);
        $dto->__set('flim', 'flam');

        self::assertEquals('flam', $dto->__get('flim'));
    }

    /**
     * @test
     * @return void
     */
    public function invalid_type_on_set_throws(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'flim' => ['null', 'string'],
            ]),
            NONE
        ));

        $this->expectException(InvalidTypeError::class);

        $dto = TestDataTransferObject::make(['flim' => 'flam'], MUTABLE);
        $dto->__set('flim', false);
    }
}
