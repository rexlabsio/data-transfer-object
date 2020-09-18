<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Factory;

use function spl_object_id;

use const Rexlabs\DataTransferObject\NONE;

/**
 * Class FactoryTest
 * @package Rexlabs\DataTransferObject
 */
class FactoryTest extends TestCase
{
    /** @var Factory */
    private $factory;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory([]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        // Clear cached static data
        // Also I'm sorry for caching static data
        DataTransferObject::setFactory(null);
    }

    /**
     * @test
     * @return void
     */
    public function caches_loaded_metadata(): void
    {
        $meta = new DTOMetadata('', [], NONE);

        $factory = new Factory(['dto_classOne' => $meta]);

        $newMeta = $factory->getDTOMetadata('classOne');

        self::assertEquals(spl_object_id($meta), spl_object_id($newMeta));
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_set(): void
    {
        $properties = [
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
            'nullable' => null,
        ];

        $object = $this->factory->makeWithPropertyTypes(
            DataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['string'],
                    'two' => ['string'],
                    'three' => ['string'],
                    'nullable' => ['null', 'string'],
                ]
            ),
            $properties,
            NONE
        );

        self::assertEquals($object->getDefinedProperties(), $properties);
    }

    /**
     * @test
     * @return void
     */
    public function properties_must_have_at_least_one_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory->makePropertyType('', []);
    }
}
