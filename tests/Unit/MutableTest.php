<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\ImmutableTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;

class MutableTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function creates_immutable_properties_by_default(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['string']]),
            NONE
        ));

        $object = TestDataTransferObject::make(['one' => 'One'], NONE);

        self::assertFalse($object->isMutable());
    }

    /**
     * @test
     * @return void
     */
    public function set_value_on_immutable_throws(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'string']]),
            NONE
        ));

        $this->expectException(ImmutableTypeError::class);

        $object = TestDataTransferObject::make(['one' => 'One'], NONE);

        $object->__set('one', 'mutation');
    }

    /**
     * @test
     * @return void
     */
    public function set_value_on_mutable_succeeds(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'string']]),
            NONE
        ));

        $object = TestDataTransferObject::make(['one' => 'One'], MUTABLE);

        $object->__set('one', 'mutation');

        self::assertEquals('mutation', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function creates_mutable_properties_when_specified(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes(['one' => ['null', 'string']]),
            NONE
        ));

        $newValue = 'mutation';

        $object = TestDataTransferObject::make(['one' => 'One'], MUTABLE);

        $object->__set('one', $newValue);

        self::assertEquals($newValue, $object->__get('one'));
    }
}
