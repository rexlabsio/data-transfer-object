<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\ClassData;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\ImmutableError;
use Rexlabs\DataTransferObject\Exceptions\InvalidFlagsException;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UninitialisedPropertiesError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesError;
use Rexlabs\DataTransferObject\Property;
use Rexlabs\DataTransferObject\Factory;

use function spl_object_id;

use const Rexlabs\DataTransferObject\ARRAY_DEFAULT_TO_EMPTY_ARRAY;
use const Rexlabs\DataTransferObject\BOOL_DEFAULT_TO_FALSE;
use const Rexlabs\DataTransferObject\IGNORE_UNKNOWN_PROPERTIES;
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\NOT_NULLABLE;
use const Rexlabs\DataTransferObject\NULLABLE;
use const Rexlabs\DataTransferObject\NULLABLE_DEFAULT_TO_NULL;
use const Rexlabs\DataTransferObject\PARTIAL;

/**
 * Class FactoryTest
 * @package Rexlabs\DataTransferObject
 */
class FactoryTest extends TestCase
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
    public function caches_loaded_metadata(): void
    {
        $meta = new DTOMetadata('', [], NONE);

        $factory = new Factory(['dto_classOne' => $meta]);

        $newMeta = $factory->getDTOMetadata('classOne');

        self::assertEquals(spl_object_id($meta), spl_object_id($newMeta));
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_set(): void
    {
        $properties = [
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
            'nullable' => null,
        ];

        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['string'], [], false, null),
                'two' => new Property($this->factory, 'two', ['string'], [], false, null),
                'three' => new Property($this->factory, 'three', ['string'], [], false, null),
                'nullable' => new Property($this->factory, 'nullable', ['null', 'string'], [], false, null),
            ],
            DataTransferObject::class,
            $properties,
            NONE
        );

        self::assertEquals($object->getDefinedProperties(), $properties);
    }

    /**
     * @test
     * @return void
     */
    public function creates_immutable_properties_by_default(): void
    {
        $this->expectException(ImmutableError::class);

        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
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

        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            ['one' => 'One'],
            MUTABLE
        );

        $object->__set('one', $newValue);

        self::assertEquals($newValue, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_returns_null(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL
        );
        $data = $object->toArray();

        self::assertArrayHasKey('one', $data);
        self::assertNull($data['one']);
    }

    /**
     * @test
     * @return void
     */
    public function can_make_partial_without_required_fields(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        self::assertNotEmpty($object);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_on_partial_returns_null(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        self::assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_omitted_by_to_array(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        self::assertArrayNotHasKey('one', $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function undefined_non_nullable_property_throws(): void
    {
        $this->expectException(UninitialisedPropertiesError::class);

        $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );
    }

    /**
     * @test
     * @return void
     */
    public function array_defaults_to_empty_array(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['array'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );


        self::assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function bool_defaults_to_false(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['bool'], [], false, null)],
            DataTransferObject::class,
            [],
            BOOL_DEFAULT_TO_FALSE
        );


        self::assertEquals(false, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function empty_array_takes_precedence_over_nullable(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        self::assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], true, 'blim')],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );


        self::assertEquals('blim', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        $this->expectException(UninitialisedPropertiesError::class);

        $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array', 'bool'], [], false, null)],
            DataTransferObject::class,
            [],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_throw_error(): void
    {
        $this->expectException(UnknownPropertiesError::class);

        $this->factory->makeWithProperties(
            [],
            DataTransferObject::class,
            ['blim' => 'blam'],
            NONE
        );
    }

    /**
     * @test
     * @return void
     */
    public function additional_properties_ignored_with_flags(): void
    {
        $object = $this->factory->makeWithProperties(
            [],
            DataTransferObject::class,
            ['blim' => 'blam'],
            IGNORE_UNKNOWN_PROPERTIES
        );

        self::assertEquals([], $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function partial_flags_makes_properties_nullable(): void
    {
        $data = ['one' => 1];
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['int'], [], false, null),
                'two' => new Property($this->factory, 'two', ['string'], [], false, null),
            ],
            DataTransferObject::class,
            $data,
            PARTIAL
        );

        self::assertEquals($data, $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function nullable_flags_overrides_not_nullable_type(): void
    {
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['int'], [], false, null),
            ],
            DataTransferObject::class,
            ['one' => null],
            NULLABLE
        );

        self::assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function not_nullable_flags_overrides_nullable_type(): void
    {
        $this->expectException(InvalidTypeError::class);

        $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['null', 'int'], [], false, null),
            ],
            DataTransferObject::class,
            ['one' => null],
            NOT_NULLABLE
        );
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_not_nullable_flags_are_incompatible(): void
    {
        $this->expectException(InvalidFlagsException::class);

        $this->factory->makeWithProperties(
            [],
            DataTransferObject::class,
            [],
            NOT_NULLABLE | NULLABLE
        );
    }

    /**
     * @test
     * @return void
     */
    public function not_nullable_partial_allows_undefined_values(): void
    {
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null),
                'two' => new Property($this->factory, 'one', ['null', 'string'], [], false, null),
            ],
            DataTransferObject::class,
            ['one' => 'blim'],
            NOT_NULLABLE | PARTIAL
        );

        $expected = [
            'one' => 'blim',
        ];
        self::assertEquals($expected, $object->toArray());
    }

    /**
     * Only effects `__get`, the value will still be absent on `toArray`
     * This is preferred to throwing type errors on `__get`
     *
     * @test
     * @return void
     */
    public function not_nullable_partial_returns_null_on_get_undefined(): void
    {
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null),
                'two' => new Property($this->factory, 'one', ['null', 'string'], [], false, null),
            ],
            DataTransferObject::class,
            ['one' => 'blim'],
            NOT_NULLABLE | PARTIAL
        );

        self::assertNull($object->__get('two'));
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

        self::assertEquals(['string'], $firstName->getTypes(NONE));
        self::assertEquals(['null', 'string'], $lastName->getTypes(NONE));
        self::assertEquals(['string[]'], $aliases->getTypes(NONE));
        self::assertEquals(['string'], $aliases->getArrayTypes());
        self::assertEquals(['null', 'Test\\TestingPhoneDto'], $phone->getTypes(NONE));
        self::assertEquals(['null', 'string'], $email->getTypes(NONE));
        self::assertEquals(['null', 'Test\\TestingAddressDto'], $address->getTypes(NONE));
        self::assertEquals(['null', 'Test\\TestingAddressDto'], $postalAddress->getTypes(NONE));
        self::assertEquals(['null', 'Test\\TestingAddressDto[]'], $otherAddresses->getTypes(NONE));
        self::assertEquals(['string'], $status->getTypes(NONE));
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
