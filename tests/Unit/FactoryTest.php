<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use InvalidArgumentException;
use Rexlabs\DataTransferObject\Factory\Factory;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use function spl_object_id;

use const Rexlabs\DataTransferObject\NONE;

/**
 * Class FactoryTest
 *
 * @package Rexlabs\DataTransferObject
 */
class FactoryTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function caches_loaded_metadata(): void
    {
        $factory = Factory::makeDefaultFactory();

        $meta = $factory->setClassMetadata('classOne', []);
        $newMeta = $factory->getClassMetadata('classOne');

        self::assertEquals(spl_object_id($meta), spl_object_id($newMeta));
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_set(): void
    {
        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'one' => ['string'],
                'two' => ['string'],
                'three' => ['string'],
                'nullable' => ['null', 'string'],
            ]
        );

        $properties = [
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
            'nullable' => null,
        ];

        $object = TestDataTransferObject::make($properties, NONE);

        self::assertEquals($object->getDefinedProperties(), $properties);
    }

    /**
     * @test
     * @return void
     */
    public function properties_must_have_at_least_one_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'test' => [],
            ]
        );
    }
}
