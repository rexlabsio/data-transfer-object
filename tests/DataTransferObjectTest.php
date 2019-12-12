<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\TransferObjects\Other\TestingPhoneDto;
use Rexlabs\DataTransferObject\TransferObjects\TestingAddressDto;
use Rexlabs\DataTransferObject\TransferObjects\TestingPersonDto;

/**
 * Class DataTransferObjectTest
 */
class DataTransferObjectTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function properties_are_set(): void
    {
        $line1 = '10 Drury Lane';
        $line2 = '';
        $suburb = 'Magic Kingdom';
        $object = new TestingAddressDto([
            'line_1' => $line1,
            'line_2' => $line2,
            'suburb' => $suburb,
        ]);

        $this->assertEquals($line1, $object->line_1);
        $this->assertEquals($line2, $object->line_2);
        $this->assertEquals($suburb, $object->suburb);
        $this->assertNull($object->postcode);
    }

    /**
     * @test
     * @return void
     */
    public function only_isset_returns_nested_values(): void
    {
        $line1 = '10 Drury Lane';
        $object = new TestingPersonDto([
            'first_name' => 'Joe',
            'phone' => [
                'number' => '1234',
            ],
            'address' => [
                'line_1' => $line1,
            ],
            'postal_address' => null,
        ]);

        $requested = [
            'first_name',
            'last_name',
            'email',
            'address.line_1',
            'postal_address',
            'postal_address.line_1',
            'alt_address.line_1',
        ];
        $setValues = $object->onlyInitialised($requested);

        $expected = [
            'first_name' => 'Joe',
            'address.line_1' => $line1,
            'postal_address' => null,
        ];
        $this->assertEquals($expected, $setValues);
        $this->assertEquals($line1, $object->address->line_1);
    }

    /**
     * @test
     * @return void
     */
    public function cast_nested_objects(): void
    {
        $object = new TestingPersonDto([
            'first_name' => 'Joe',
            'phone' => [
                'number' => '1234',
            ],
            'address' => [
                'line_1' => '10 Drury Lane',
            ],
            'postal_address' => null,
        ]);

        $this->assertInstanceOf(TestingAddressDto::class, $object->address);
        $this->assertInstanceOf(TestingPhoneDto::class, $object->phone);
        $this->assertNull($object->postal_address);
    }

    /**
     * @test
     * @return void
     */
    public function non_nullable_array_defaults_to_empty_array(): void
    {
        $object = new TestingPersonDto([
            'first_name' => 'Joe',
        ]);

        $this->assertIsArray($object->aliases);
    }
}
