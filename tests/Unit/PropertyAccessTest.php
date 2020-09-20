<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\PropertyType;

use const Rexlabs\DataTransferObject\PARTIAL;

class PropertyAccessTest extends TestCase
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
     *
     * @return void
     */
    public function can_get_only_defined_properties_from_partial(): void
    {
        $values = [
            'blim' => 'test',
            'blam' => true,
        ];

        $dto = new DataTransferObject(
            [
                'blim' => $this->createMock(PropertyType::class),
                'blam' => $this->createMock(PropertyType::class),
                'flim' => $this->createMock(PropertyType::class),
                'flam' => $this->createMock(PropertyType::class),
            ],
            $values,
            PARTIAL
        );

        self::assertEquals($values, $dto->getDefinedProperties());
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_get_defined_properties_with_defaults(): void
    {
        $factory = new Factory([]);
        $dto = new DataTransferObject(
            $factory->makePropertyTypes([
                'blim' => [],
                'blam' => [],
                'flim' => ['array'],
                'flam' => ['null', 'array'],
            ]),
            [
                'blim' => 'test',
                'blam' => true
            ],
            PARTIAL
        );

        $expected = [
            'blim' => 'test',
            'blam' => true,
            'flim' => [],
            'flam' => null,
        ];

        self::assertEquals($expected, $dto->getPropertiesWithDefaults());
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_get_undefined_property_names(): void
    {
        $factory = new Factory([]);
        $dto = new DataTransferObject(
            $factory->makePropertyTypes([
                'blim' => [],
                'blam' => [],
                'flim' => ['array'],
                'flam' => ['null', 'array'],
            ]),
            [
                'blim' => 'test',
                'blam' => true
            ],
            PARTIAL
        );

        $expected = [
            'flim',
            'flam',
        ];

        self::assertEquals($expected, $dto->getUndefinedPropertyNames());
    }

    /**
     * @test
     *
     * @return void
     */
    public function can_get_defined_property_names(): void
    {
        $factory = new Factory([]);
        $dto = new DataTransferObject(
            $factory->makePropertyTypes([
                'blim' => [],
                'blam' => [],
                'flim' => ['array'],
                'flam' => ['null', 'array'],
            ]),
            [
                'blim' => 'test',
                'blam' => true
            ],
            PARTIAL
        );

        $expected = [
            'blim',
            'blam',
        ];

        self::assertEquals($expected, $dto->getDefinedPropertyNames());
    }
}
