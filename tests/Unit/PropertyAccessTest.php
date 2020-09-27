<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class PropertyAccessTest extends TestCase
{
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

        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['null'],
                    'flam' => ['null'],
                ]
            )
            ->propertyTypes;

        $dto = new TestDataTransferObject(
            $propertyTypes,
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
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['array'],
                    'flam' => ['null', 'array'],
                ]
            )
            ->propertyTypes;

        $dto = new TestDataTransferObject(
            $propertyTypes,
            [
                'blim' => 'test',
                'blam' => true,
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
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['array'],
                    'flam' => ['null', 'array'],
                ]
            )
            ->propertyTypes;

        $dto = new TestDataTransferObject(
            $propertyTypes,
            [
                'blim' => 'test',
                'blam' => true,
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
        $propertyTypes = $this->factory
            ->setClassMetadata(
                TestDataTransferObject::class,
                [
                    'blim' => ['null'],
                    'blam' => ['null'],
                    'flim' => ['array'],
                    'flam' => ['null', 'array'],
                ]
            )
            ->propertyTypes;

        $dto = new TestDataTransferObject(
            $propertyTypes,
            [
                'blim' => 'test',
                'blam' => true,
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
