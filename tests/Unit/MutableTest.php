<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Factory;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;

class MutableTest extends TestCase
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
    public function creates_immutable_properties_by_default(): void
    {
        $this->expectException(ImmutableError::class);

        $object = $this->factory->makeWithPropertyTypes(
            ['one' => $this->factory->makePropertyType('one', ['null', 'string'])],
            DataTransferObject::class,
            ['one' => 'One'],
            NONE
        );

        $object->__set('one', 'mutation');
    }

    /**
     * @test
     * @return void
     */
    public function creates_mutable_properties_when_specified(): void
    {
        $newValue = 'mutation';

        $object = $this->factory->makeWithPropertyTypes(
            ['one' => $this->factory->makePropertyType('one', ['null', 'string'])],
            DataTransferObject::class,
            ['one' => 'One'],
            MUTABLE
        );

        $object->__set('one', $newValue);

        self::assertEquals($newValue, $object->__get('one'));
    }
}
