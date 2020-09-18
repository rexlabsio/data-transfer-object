<?php

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
use Rexlabs\DataTransferObject\Factory;

use const Rexlabs\DataTransferObject\NONE;

class IssetIsDefinedTest extends TestCase
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
    public function defined_property_returns_isset_true(): void
    {
        $object = new DataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['bool'])],
            ['blim' => true],
            [],
            NONE
        );

        self::assertTrue(isset($object->blim));
    }

    /**
     * See php isset documentation
     *
     * @test
     * @return void
     */
    public function defined_null_value_property_returns_isset_false(): void
    {
        $object = new DataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['null'])],
            ['blim' => null],
            [],
            NONE
        );

        self::assertFalse(isset($object->blim));
    }

    /**
     * @test
     * @return void
     */
    public function defined_to_anything_properties_return_is_defined_true(): void
    {
        $object = new DataTransferObject(
            [
                'blim' => $this->factory->makePropertyType('blim', ['null']),
                'blam' => $this->factory->makePropertyType('blam', ['bool']),
            ],
            [
            'blim' => null,
            'blam' => true
            ],
            [],
            NONE
        );

        self::assertTrue($object->isDefined('blim'));
        self::assertTrue($object->isDefined('blam'));
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_supports_dot_notation_for_nested_properties(): void
    {
        $object = new DataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['mixed'])],
            [
                'blim' => new DataTransferObject(
                    ['blam' => $this->factory->makePropertyType('blam', ['mixed'])],
                    [
                        'blam' => new DataTransferObject(
                            ['beep' => $this->factory->makePropertyType('beep', ['mixed'])],
                            ['beep' => true],
                            [],
                            NONE
                        )
                    ],
                    [],
                    NONE
                )
            ],
            [],
            NONE
        );

        self::assertTrue($object->isDefined('blim.blam.beep'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_property_returns_is_defined_false(): void
    {
        $object = new DataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['null'])],
            [],
            [],
            NONE
        );

        self::assertFalse($object->isDefined('blim'));
    }

    /**
     * @test
     * @return void
     */
    public function unknown_property_is_defined_throws(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $object = new DataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['null'])],
            [],
            [],
            NONE
        );

        self::assertFalse($object->isDefined('blam'));
    }
}
