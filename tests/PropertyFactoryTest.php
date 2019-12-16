<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Property;
use Rexlabs\DataTransferObject\PropertyFactory;
use Rexlabs\DataTransferObject\Tests\TransferObjects\TestingPersonDto;

use function spl_object_id;

/**
 * Class PropertyFactoryTest
 * @package Rexlabs\DataTransferObject
 */
class PropertyFactoryTest extends TestCase
{
    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        // Clear cached static data
        // Also I'm sorry for caching static data
        DataTransferObject::setPropertyFactory(null);
    }

    /**
     * @test
     * @return void
     */
    public function load_class_data_from_class_name(): void
    {
        $factory = new PropertyFactory(collect([]));

        $classData = $factory->loadClassData(TestingPersonDto::class);

        $this->assertNotEmpty($classData->docComment);
        $this->assertNotEmpty($classData->defaults);
        $this->assertNotEmpty($classData->useStatements);
        $this->assertNotEmpty($classData->namespace);
    }

    /**
     * @test
     * @return void
     */
    public function caches_loaded_property_types(): void
    {
        $property = new Property('propOne', [], [], false, null);
        $typeId = spl_object_id($property);

        $factory = new PropertyFactory(collect([
            'classOne' => collect([
                'propOne' => $property,
            ]),
        ]));

        $types = $factory->propertyTypes('classOne');

        $this->assertEquals($typeId, spl_object_id($types->first()));
    }

    /**
     * Sharing the collection could accidentally introduce side effects into
     * cached properties.
     *
     * @test
     * @return void
     */
    public function does_not_share_cached_collection_of_types(): void
    {
        $types = collect([
            'propOne' => new Property('propOne', [], [], false, null),
        ]);
        $typeId = spl_object_id($types);

        $factory = new PropertyFactory(collect([
            'classOne' => $types,
        ]));

        $types = $factory->propertyTypes('classOne');

        $this->assertNotEquals($typeId, spl_object_id($types));
    }
}
