<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\ClassData;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\NONE;

class IssetIsDefinedTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function defined_property_returns_isset_true(): void
    {
        $object = new TestDataTransferObject(
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
        $object = new TestDataTransferObject(
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
        $object = new TestDataTransferObject(
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
        $object = new TestDataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['mixed'])],
            [
                'blim' => new TestDataTransferObject(
                    ['blam' => $this->factory->makePropertyType('blam', ['mixed'])],
                    [
                        'blam' => new TestDataTransferObject(
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
    public function is_defined_supports_dot_notation_for_nested_arrays(): void
    {
        $object = new TestDataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['mixed'])],
            [
                'blim' => new TestDataTransferObject(
                    ['blam' => $this->factory->makePropertyType('blam', ['array'])],
                    [
                        'blam' => [
                            'beep' => [
                                'boop' => true,
                            ],
                        ],
                    ],
                    [],
                    NONE
                )
            ],
            [],
            NONE
        );

        self::assertTrue($object->isDefined('blim.blam.beep.boop'));
    }

    /**
     * @test
     * @return void
     */
    public function is_defined_supports_dot_notation_for_nested_objects(): void
    {
        $object = new TestDataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['mixed'])],
            [
                'blim' => new TestDataTransferObject(
                    ['blam' => $this->factory->makePropertyType('blam', ['array'])],
                    [
                        'blam' => [
                            'beep' => (object)[
                                'boop' => new ClassData('test', '', '', [], NONE)
                            ],
                        ],
                    ],
                    [],
                    NONE
                )
            ],
            [],
            NONE
        );

        self::assertTrue($object->isDefined('blim.blam.beep.boop'));
        self::assertTrue($object->isDefined('blim.blam.beep.boop.namespace'));
        self::assertEquals('test', $object->__get('blim')->__get('blam')['beep']->boop->namespace);
    }

    /**
     * @test
     * @return void
     */
    public function nested_unknown_properties_show_path(): void
    {
        $object = new TestDataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['mixed'])],
            [
                'blim' => new TestDataTransferObject(
                    ['blam' => $this->factory->makePropertyType('blam', ['mixed'])],
                    [
                        'blam' => new TestDataTransferObject(
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

        try {
            self::assertTrue($object->isDefined('blim.blam.braawwwwwwwwp.beep'));
            self::fail('Expected have thrown unknown property exception');
            return;
        } catch (UnknownPropertiesTypeError $e) {
            self::assertRegExp('/\\n.*\bblim.blam.braawwwwwwwwp\b$/', $e->getMessage());
        }
    }

    /**
     * @test
     * @return void
     */
    public function undefined_property_returns_is_defined_false(): void
    {
        $object = new TestDataTransferObject(
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
        $this->expectException(UnknownPropertiesTypeError::class);

        $object = new TestDataTransferObject(
            ['blim' => $this->factory->makePropertyType('blim', ['null'])],
            [],
            [],
            NONE
        );

        self::assertFalse($object->isDefined('blam'));
    }
}
