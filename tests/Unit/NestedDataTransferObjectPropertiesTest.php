<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class NestedDataTransferObjectPropertiesTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function can_make_with_nested_objects(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [

                'id' => ['string'],
                'first_name' => ['string'],
                'last_name' => ['null', 'string'],
                'parent' => ['null', TestDataTransferObject::class],
                'partner' => ['null', TestDataTransferObject::class],
                'siblings' => ['null', TestDataTransferObject::class . '[]'],
            ]
        );

        $object = TestDataTransferObject::make(
            [
                'id' => 'test_id',
                'first_name' => 'Joe',
                'last_name' => 'Dirt',
                'parent' => [
                    'id' => 'test_id_2',
                    'first_name' => 'Geoff',
                    'last_name' => 'Dirt',
                ],
                'partner' => [
                    'id' => 'test_id_3',
                    'first_name' => 'Jill',
                    'last_name' => 'Dirt',
                ],
                'siblings' => [
                    [
                        'id' => 'test_id_4',
                        'first_name' => 'Dave',
                        'last_name' => 'Dirt',

                    ],
                    [
                        'id' => 'test_id_5',
                        'first_name' => 'Carl',
                        'last_name' => 'Dirt',
                    ],
                ],
            ],
            PARTIAL
        );
        $parent = $object->__get('parent');
        $partner = $object->__get('partner');
        $siblings = $object->__get('siblings');

        self::assertEquals('test_id', $object->__get('id'));
        self::assertEquals('Joe', $object->__get('first_name'));
        self::assertEquals('Dirt', $object->__get('last_name'));

        self::assertInstanceOf(TestDataTransferObject::class, $parent);
        self::assertEquals('test_id_2', $parent->__get('id'));
        self::assertEquals('Geoff', $parent->__get('first_name'));
        self::assertEquals('Dirt', $parent->__get('last_name'));

        self::assertInstanceOf(TestDataTransferObject::class, $partner);
        self::assertEquals('test_id_3', $partner->__get('id'));
        self::assertEquals('Jill', $partner->__get('first_name'));
        self::assertEquals('Dirt', $partner->__get('last_name'));

        self::assertCount(2, $siblings);
        foreach ($siblings as $sibling) {
            self::assertInstanceOf(TestDataTransferObject::class, $sibling);
        }
    }
}
