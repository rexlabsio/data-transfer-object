<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;

use const Rexlabs\DataTransferObject\PARTIAL;

class PropertyAccessTest extends TestCase
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
                'blim' => $this->factory->makePropertyType('blim', ['null']),
                'blam' => $this->factory->makePropertyType('blam', ['null']),
                'flim' => $this->factory->makePropertyType('flim', ['null']),
                'flam' => $this->factory->makePropertyType('flam', ['null']),
            ],
            $values,
            [],
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
            $factory->makePropertyTypes(
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['array'],
                    'flam' => ['null', 'array'],
                ]
            ),
            [
            'blim' => 'test',
            'blam' => true
            ],
            [],
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
            $factory->makePropertyTypes(
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['array'],
                    'flam' => ['null', 'array'],
                ]
            ),
            [
            'blim' => 'test',
            'blam' => true
            ],
            [],
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
            $factory->makePropertyTypes(
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['array'],
                    'flam' => ['null', 'array'],
                ]
            ),
            [
            'blim' => 'test',
            'blam' => true
            ],
            [],
            PARTIAL
        );

        $expected = [
            'blim',
            'blam',
        ];

        self::assertEquals($expected, $dto->getDefinedPropertyNames());
    }
}
