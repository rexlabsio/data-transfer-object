<?php

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\ClassData;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\Property;

use const Rexlabs\DataTransferObject\NONE;

class DocParseTest extends TestCase
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
    public function extract_use_statements_with_aliases(): void
    {
        $code = <<< 'TEXT'
<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\TransferObjects;

use Acme\TestingPhoneDto as Phone;
use Acme\TestingAddressDto;

class TestingPersonDto extends DataTransferObject
TEXT;

        $expected = [
            'Phone' => 'Acme\\TestingPhoneDto',
            'TestingAddressDto' => 'Acme\\TestingAddressDto',
        ];

        self::assertEquals($expected, $this->factory->extractUseStatements($code));
    }

    /**
     * @test
     * @return void
     */
    public function extract_use_statements_with_leading_slashes(): void
    {
        $code = <<< 'TEXT'
<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\TransferObjects;

use \Acme\TestingAddressDto;

class TestingPersonDto extends DataTransferObject
TEXT;

        $expected = [
            'TestingAddressDto' => 'Acme\\TestingAddressDto',
        ];

        self::assertEquals($expected, $this->factory->extractUseStatements($code));
    }

    /**
     * @test
     * @return void
     */
    public function extract_property_types_from_class_data(): void
    {
        $useStatements = [
            'Phone' => 'Test\\TestingPhoneDto',
            'TestingAddressDto' => 'Test\\TestingAddressDto',
        ];

        $docComment = <<<'TEXT'
/**
 * @property string $first_name
 * @property null|string $last_name
 * @property string[] $aliases
 * @property-read null|Phone $phone
 * @property-read null|string $email
 * @property-read null|TestingAddressDto $address
 * @property-read null|Test\TestingAddressDto $postal_address
 * @property-read null|TestingAddressDto[] $other_addresses
 * @property-read string $status
 */
TEXT;

        $classData = new ClassData(
            'Test\\Namespace\\DTO',
            '',
            $docComment,
            [],
            NONE
        );

        /**
         * @var Property $firstName
         * @var Property $lastName
         * @var Property $aliases
         * @var Property $phone
         * @var Property $email
         * @var Property $address
         * @var Property $postalAddress
         * @var Property $otherAddresses
         * @var Property $status
         */
        [
            $firstName,
            $lastName,
            $aliases,
            $phone,
            $email,
            $address,
            $postalAddress,
            $otherAddresses,
            $status,
        ] = array_values($this->factory->mapClassToPropertyTypes($classData, $useStatements));

        self::assertEquals(['string'], $firstName->getTypes());
        self::assertEquals(['null', 'string'], $lastName->getTypes());
        self::assertEquals(['string[]'], $aliases->getTypes());
        self::assertEquals(['string'], $aliases->getArrayTypes());
        self::assertEquals(['null', 'Test\\TestingPhoneDto'], $phone->getTypes());
        self::assertEquals(['null', 'string'], $email->getTypes());
        self::assertEquals(['null', 'Test\\TestingAddressDto'], $address->getTypes());
        self::assertEquals(['null', 'Test\\TestingAddressDto'], $postalAddress->getTypes());
        self::assertEquals(['null', 'Test\\TestingAddressDto[]'], $otherAddresses->getTypes());
        self::assertEquals(['string'], $status->getTypes());
    }

    /**
     * @test
     * @return void
     */
    public function can_map_simple_types(): void
    {
        $type = $this->factory->mapType('Phone', null, ['Phone' => 'Acme\\Test\\TestingPhoneDto']);

        self::assertEquals('Acme\\Test\\TestingPhoneDto', $type);
    }

    /**
     * @test
     * @return void
     */
    public function can_map_type_with_leading_slash(): void
    {
        /**
         * @var Factory|MockObject $factory
         */
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['classExists'])
            ->getMock();

        $factory->method('classExists')->willReturn(true);

        $type = $factory->mapType(
            '\\Acme\\Test\\TestingPhoneDto',
            null,
            ['Phone' => 'Acme\\Test\\TestingPhoneDto']
        );

        self::assertEquals('Acme\\Test\\TestingPhoneDto', $type);
    }
}
