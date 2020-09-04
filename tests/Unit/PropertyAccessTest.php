<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\Property;

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
                'blim' => $this->createMock(Property::class),
                'blam' => $this->createMock(Property::class),
                'flim' => $this->createMock(Property::class),
                'flam' => $this->createMock(Property::class),
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
            [
                'blim' => $this->createMock(Property::class),
                'blam' => $this->createMock(Property::class),
                'flim' => new Property($factory, 'flim', ['array'], [], true, []),
                'flam' => new Property($factory, 'flam', ['array', 'null'], [], true, null),
            ],
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
            [
                'blim' => $this->createMock(Property::class),
                'blam' => $this->createMock(Property::class),
                'flim' => new Property($factory, 'flim', ['array'], [], true, []),
                'flam' => new Property($factory, 'flam', ['array'], [], true, null),
            ],
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
            [
                'blim' => $this->createMock(Property::class),
                'blam' => $this->createMock(Property::class),
                'flim' => new Property($factory, 'flim', ['array'], [], true, []),
                'flam' => new Property($factory, 'flam', ['array'], [], true, null),
            ],
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
