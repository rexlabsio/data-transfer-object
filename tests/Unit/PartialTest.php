<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class PartialTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function can_make_partial_without_required_fields(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['string'])],
            NONE
        ));

        $object = DataTransferObject::make(
            [],
            PARTIAL
        );

        self::assertNotEmpty($object);
        self::assertEquals(['one'], $object->getUndefinedPropertyNames());
    }

    /**
     * @test
     * @return void
     */
    public function partial_to_array_returns_only_defined(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            DataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['int'],
                    'two' => ['string', 'bool'],
                ],
                ['two' => true]
            ),
            NONE
        ));

        $data = ['one' => 1];
        $object = DataTransferObject::make(
            $data,
            PARTIAL
        );

        self::assertEquals($data, $object->toArray());
    }


    /**
     * @test
     * @return void
     */
    public function partial_get_properties_returns_only_defined(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            DataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['int'],
                    'two' => ['string', 'bool'],
                ],
                ['two' => true]
            ),
            NONE
        ));

        $data = ['one' => 1];
        $object = DataTransferObject::make(
            $data,
            PARTIAL
        );

        self::assertEquals($data, $object->getDefinedProperties());
    }
}
