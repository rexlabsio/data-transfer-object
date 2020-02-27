<?php

namespace Rexlabs\DataTransferObject\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingDto;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingNestableDto;

/**
 * Class NestableDtoTest
 * @package Rexlabs\DataTransferObject\Tests\Feature
 */
class NestableDtoTest extends TestCase
{
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
    public function load_class_data_from_class_name(): void
    {
        $factory = new Factory([]);
        $classData = $factory->extractClassData(TestingNestableDto::class);

        $this->assertNotEmpty($classData->docComment);
        $this->assertIsArray($classData->defaults);
        $this->assertNotEmpty($classData->contents);
        $this->assertNotEmpty($classData->namespace);
    }

    /**
     * @test
     * @return void
     */
    public function can_make_with_nested_objects(): void
    {
        $object = TestingNestableDto::make([
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
        ]);
        $parent = $object->parent;
        $partner = $object->partner;
        $siblings = $object->siblings;

        $this->assertEquals('test_id', $object->id);
        $this->assertEquals('Joe', $object->first_name);
        $this->assertEquals('Dirt', $object->last_name);

        $this->assertInstanceOf(TestingNestableDto::class, $parent);
        $this->assertEquals('test_id_2', $parent->id);
        $this->assertEquals('Geoff', $parent->first_name);
        $this->assertEquals('Dirt', $parent->last_name);

        $this->assertInstanceOf(TestingNestableDto::class, $partner);
        $this->assertEquals('test_id_3', $partner->id);
        $this->assertEquals('Jill', $partner->first_name);
        $this->assertEquals('Dirt', $partner->last_name);

        $this->assertCount(2, $siblings);
        foreach ($siblings as $sibling) {
            $this->assertInstanceOf(TestingNestableDto::class, $sibling);
        }
    }

    /**
     * @test
     * @return void
     */
    public function can_make_record(): void
    {
        $parameters = [
            'captain' => [
                'id' => 'test_id_1',
                'first_name' => 'James',
                'last_name' => 'Kirk',
            ],
            'science_officer' => [
                'id' => 'test_id_3',
                'first_name' => 'S\'chn',
                'last_name' => 'Spock',
            ],
            'chief_medical_officer' => [
                'id' => 'test_id_4',
                'first_name' => 'Leonard',
                'last_name' => 'McCoy',
            ],
        ];

        $propertyNames = [
            'captain',
            'science_officer',
            'chief_medical_officer',
        ];
        $object = TestingNestableDto::makeRecord($propertyNames, $parameters);

        foreach ($propertyNames as $propertyName) {
            $this->assertInstanceOf(
                TestingNestableDto::class,
                $object->__get($propertyName)
            );
        }
    }

    /**
     * @test
     * @return void
     */
    public function can_make_pick(): void
    {
        $propertyNames = [
            'parent',
            'partner',
        ];
        $object = TestingNestableDto::makePick(
            $propertyNames,
            [
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
            ]
        );
        $parent = $object->parent;
        $partner = $object->partner;

        $this->assertCount(count($propertyNames), $object->getProperties());

        $this->assertInstanceOf(TestingNestableDto::class, $parent);
        $this->assertEquals('test_id_2', $parent->id);
        $this->assertEquals('Geoff', $parent->first_name);
        $this->assertEquals('Dirt', $parent->last_name);

        $this->assertInstanceOf(TestingNestableDto::class, $partner);
        $this->assertEquals('test_id_3', $partner->id);
        $this->assertEquals('Jill', $partner->first_name);
        $this->assertEquals('Dirt', $partner->last_name);
    }

    /**
     * @test
     * @return void
     */
    public function can_make_omit(): void
    {
        $propertyNames = [
            'id',
            'first_name',
            'last_name',
            'siblings',
        ];
        $properties = [
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
        ];
        $object = TestingNestableDto::makeOmit($propertyNames, $properties);
        $parent = $object->parent;
        $partner = $object->partner;

        $this->assertCount(count($properties), $object->getProperties());

        $this->assertInstanceOf(TestingNestableDto::class, $parent);
        $this->assertEquals('test_id_2', $parent->id);
        $this->assertEquals('Geoff', $parent->first_name);
        $this->assertEquals('Dirt', $parent->last_name);

        $this->assertInstanceOf(TestingNestableDto::class, $partner);
        $this->assertEquals('test_id_3', $partner->id);
        $this->assertEquals('Jill', $partner->first_name);
        $this->assertEquals('Dirt', $partner->last_name);
    }

    /**
     * @test
     * @return void
     */
    public function can_make_exclude(): void
    {
        $properties = [
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
            'siblings' => [],
        ];
        $object = TestingNestableDto::makeExclude(TestingDto::class, $properties);
        $parent = $object->parent;
        $partner = $object->partner;

        $this->assertCount(count($properties), $object->getProperties());

        $this->assertInstanceOf(TestingNestableDto::class, $parent);
        $this->assertEquals('test_id_2', $parent->id);
        $this->assertEquals('Geoff', $parent->first_name);
        $this->assertEquals('Dirt', $parent->last_name);

        $this->assertInstanceOf(TestingNestableDto::class, $partner);
        $this->assertEquals('test_id_3', $partner->id);
        $this->assertEquals('Jill', $partner->first_name);
        $this->assertEquals('Dirt', $partner->last_name);
    }

    /**
     * @test
     * @return void
     */
    public function can_make_extract(): void
    {
        $properties = [
            'id' => 'test_id_1',
            'first_name' => 'James',
            'last_name' => 'Kirk',
        ];
        $object = TestingNestableDto::makeExtract(TestingDto::class, $properties);

        $this->assertCount(count($properties), $object->getProperties());

        $this->assertEquals('test_id_1', $object->id);
        $this->assertEquals('James', $object->first_name);
        $this->assertEquals('Kirk', $object->last_name);
    }
}