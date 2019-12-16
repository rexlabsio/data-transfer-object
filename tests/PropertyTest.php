<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Tests\TransferObjects\Other\TestingPhoneDto;
use Rexlabs\DataTransferObject\Tests\TransferObjects\TestingAddressDto;
use Rexlabs\DataTransferObject\Tests\TransferObjects\TestingPersonDto;

/**
 * Class PropertyTest
 * @package Rexlabs\DataTransferObject
 */
class PropertyTest extends TestCase
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
     * TODO update to test at the appropriate level
     *
     * @test
     * @return void
     */
    public function cast_nested_objects(): void
    {
        $object = TestingPersonDto::make(
            [
                'first_name' => 'Joe',
                'phone' => [
                    'number' => '1234',
                ],
                'address' => [
                    'line_1' => '10 Drury Lane',
                ],
                'postal_address' => null,
            ]
        );

        $this->assertInstanceOf(TestingAddressDto::class, $object->address);
        $this->assertInstanceOf(TestingPhoneDto::class, $object->phone);
        $this->assertNull($object->postal_address);
    }
}
