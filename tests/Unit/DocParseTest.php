<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\ClassData\ClassDataProvider;
use Rexlabs\DataTransferObject\Tests\TestCase;

class DocParseTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function extract_use_statements_with_aliases(): void
    {
        $provider = new ClassDataProvider();

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

        self::assertEquals($expected, $provider->extractUseStatements($code));
    }

    /**
     * @test
     * @return void
     */
    public function extract_use_statements_with_leading_slashes(): void
    {
        $provider = new ClassDataProvider();

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

        self::assertEquals($expected, $provider->extractUseStatements($code));
    }

    /**
     * @test
     * @return void
     */
    public function extract_property_types_from_class_data(): void
    {
        $provider = new ClassDataProvider();

        $namespace = 'Test\\Namespace\\DTO';

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

        $propertyTypesMap = $provider->extractPropertyTypesMap(
            $docComment,
            $namespace,
            $useStatements
        );

        $expected = [
            'first_name' => ['string'],
            'last_name' => ['null', 'string'],
            'aliases' => ['string[]'],
            'phone' => ['null', 'Test\\TestingPhoneDto'],
            'email' => ['null', 'string'],
            'address' => ['null', 'Test\TestingAddressDto'],
            'postal_address' => ['null', 'Test\TestingAddressDto'],
            'other_addresses' => ['null', 'Test\TestingAddressDto[]'],
            'status' => ['string'],
        ];

        self::assertEquals($expected, $propertyTypesMap);
    }

    /**
     * @test
     * @return void
     */
    public function can_map_simple_types(): void
    {
        $classDataProvider = new ClassDataProvider();
        $type = $classDataProvider->parseDocType('Phone', null, ['Phone' => 'Acme\\Test\\TestingPhoneDto']);

        self::assertEquals('Acme\\Test\\TestingPhoneDto', $type);
    }

    /**
     * @uses \Rexlabs\DataTransferObject\ClassData\ClassDataProvider::classExists
     *
     * @test
     * @return void
     */
    public function can_map_type_with_leading_slash(): void
    {
        $provider = $this->createPartialMock(ClassDataProvider::class, ['classExists']);
        $provider->method('classExists')->willReturn(true);

        $type = $provider->parseDocType(
            '\\Acme\\Test\\TestingPhoneDto',
            null,
            ['Phone' => 'Acme\\Test\\TestingPhoneDto']
        );

        self::assertEquals('Acme\\Test\\TestingPhoneDto', $type);
    }
}
