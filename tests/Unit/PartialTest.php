<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;

use const Rexlabs\DataTransferObject\PARTIAL;

class PartialTest extends TestCase
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
    public function can_make_partial_without_required_fields(): void
    {
        $object = $this->factory->make(
            DataTransferObject::class,
            ['one' => $this->factory->makePropertyType('one', ['string'])],
            [],
            PARTIAL
        );

        self::assertNotEmpty($object);
    }

    /**
     * @test
     * @return void
     */
    public function partial_to_array_returns_only_defined(): void
    {
        $data = ['one' => 1];
        $object = $this->factory->make(
            DataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['int'],
                    'two' => ['string', 'bool'],
                ],
                ['two' => true]
            ),
            $data,
            PARTIAL
        );

        self::assertEquals($data, $object->toArray());
    }


    /**
     * @test
     * @return void
     */
    public function partial_get_properties_returns_only_defined(): void
    {
        $data = ['one' => 1];
        $object = $this->factory->make(
            DataTransferObject::class,
            $this->factory->makePropertyTypes(
                [
                    'one' => ['int'],
                    'two' => ['string', 'bool'],
                ],
                ['two' => true]
            ),
            $data,
            PARTIAL
        );

        self::assertEquals($data, $object->getDefinedProperties());
    }
}
