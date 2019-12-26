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

        $this->assertEquals(spl_object_id($meta), spl_object_id($newMeta));
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

        /**
         * @var DataTransferObject $object
         */
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

        $this->assertEquals($object->getProperties(), $properties);
    }

    /**
     * @test
     * @return void
     */
    public function properties_are_not_mutable(): void
    {
        $this->expectException(ImmutableError::class);

        /**
         * @var DataTransferObject $object
         */
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
    public function mutable_flag_enables_property_mutation(): void
    {
        $newValue = 'mutation';

        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            ['one' => 'One'],
            MUTABLE
        );

        $object->__set('one', $newValue);

        $this->assertEquals($newValue, $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_returns_null(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'string'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL
        );
        $data = $object->toArray();

        $this->assertArrayHasKey('one', $data);
        $this->assertNull($data['one']);
    }

    /**
     * @test
     * @return void
     */
    public function partial_can_initialise_without_required_fields(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        $this->assertNotEmpty($object);
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_on_partial_returns_null(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        $this->assertNull($object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function undefined_nullable_property_omitted_by_to_array(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['string'], [], false, null)],
            DataTransferObject::class,
            [],
            PARTIAL
        );

        $this->assertArrayNotHasKey('one', $object->toArray());
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
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['array'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );


        $this->assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function empty_array_takes_precedence_over_nullable(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], false, null)],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );

        $this->assertEquals([], $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function default_takes_precedence_over_nullable_or_empty_array(): void
    {
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], true, 'blim')],
            DataTransferObject::class,
            [],
            NULLABLE_DEFAULT_TO_NULL | ARRAY_DEFAULT_TO_EMPTY_ARRAY
        );


        $this->assertEquals('blim', $object->__get('one'));
    }

    /**
     * @test
     * @return void
     */
    public function nullable_and_array_defaults_ignored_without_flags(): void
    {
        $this->expectException(UninitialisedPropertiesError::class);

        $this->factory->makeWithProperties(
            ['one' => new Property($this->factory, 'one', ['null', 'array'], [], false, null)],
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
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            [],
            DataTransferObject::class,
            ['blim' => 'blam'],
            IGNORE_UNKNOWN_PROPERTIES
        );

        $this->assertEquals([], $object->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function partial_flags_makes_properties_nullable(): void
    {
        $data = ['one' => 1];
        /**
         * @var DataTransferObject $object
         */
        $object = $this->factory->makeWithProperties(
            [
                'one' => new Property($this->factory, 'one', ['int'], [], false, null),
                'two' => new Property($this->factory, 'two', ['string'], [], false, null),
            ],
            DataTransferObject::class,
            $data,
            PARTIAL
        );

        $this->assertEquals($data, $object->toArray());
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

        $this->assertNull($object->__get('one'));
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
        $this->assertEquals($expected, $object->toArray());
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

        $this->assertNull($object->__get('two'));
    }

    /**
     * @test
     * @return void
     */
    public function make_record_metadata_with_all_props_of_type(): void
    {
        $names = [
            'one',
            'two',
            'three',
        ];
        $meta = $this->factory->getDTORecordMetadata(DataTransferObject::class, $names);

        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $meta->propertyTypes);
            $this->assertEquals(
                [DataTransferObject::class],
                $meta->propertyTypes[$name]->getTypes(NONE)
            );
        }
    }

    /**
     * @test
     * @return void
     */
    public function make_pick_metadata_subset_of_props(): void
    {
        /**
         * @var Factory|MockObject $factory
         */
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getDTOMetadata'])
            ->getMock();

        $factory->method('getDTOMetadata')
            ->willReturn(new DTOMetadata(
                DataTransferObject::class,
                [
                    'one' => new Property($factory, 'one', [], [], false, null),
                    'two' => new Property($factory, 'two', [], [], false, null),
                    'three' => new Property($factory, 'three', [], [], false, null),
                    'four' => new Property($factory, 'four', [], [], false, null),
                ],
                NONE
            ));

        $propertyNames = [
            'one',
            'four',
        ];
        $meta = $factory->getDTOPickMetadata(DataTransferObject::class, $propertyNames);

        $this->assertCount(count($propertyNames), $meta->propertyTypes);
        foreach ($propertyNames as $name) {
            $this->assertArrayHasKey($name, $meta->propertyTypes);
            $this->assertEquals($name, $meta->propertyTypes[$name]->getName());
        }
    }

    /**
     * @test
     * @return void
     */
    public function make_omit_metadata_subset_of_props(): void
    {
        /**
         * @var Factory|MockObject $factory
         */
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getDTOMetadata'])
            ->getMock();

        $factory->method('getDTOMetadata')
            ->willReturn(new DTOMetadata(
                DataTransferObject::class,
                [
                    'one' => new Property($factory, 'one', [], [], false, null),
                    'two' => new Property($factory, 'two', [], [], false, null),
                    'three' => new Property($factory, 'three', [], [], false, null),
                    'four' => new Property($factory, 'four', [], [], false, null),
                ],
                NONE
            ));

        $propertyNames = [
            'one',
            'four',
        ];
        $meta = $factory->getDTOOmitMetadata(DataTransferObject::class, $propertyNames);

        $this->assertCount(count($propertyNames), $meta->propertyTypes);
        foreach ($propertyNames as $name) {
            $this->assertArrayNotHasKey($name, $meta->propertyTypes);
        }

        foreach ($meta->propertyTypes as $propertyType) {
            $this->assertNotContains($propertyType->getName(), $propertyNames);
        }
    }

    /**
     * @test
     * @return void
     */
    public function make_exclude_metadata_subset_of_props(): void
    {
        /**
         * @var Factory|MockObject $factory
         */
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getDTOMetadata'])
            ->getMock();

        $metadata = [
            'standard' => new DTOMetadata(DataTransferObject::class, [
                'one' => new Property($factory, 'one', [], [], false, null),
                'two' => new Property($factory, 'two', [], [], false, null),
                'three' => new Property($factory, 'three', [], [], false, null),
                'four' => new Property($factory, 'four', [], [], false, null),
            ], NONE),
            'exclude' => new DTOMetadata(DataTransferObject::class, [
                'three' => new Property($factory, 'three', [], [], false, null),
                'four' => new Property($factory, 'four', [], [], false, null),
                'five' => new Property($factory, 'two', [], [], false, null),
            ], NONE),
        ];

        $factory->method('getDTOMetadata')
            ->willReturnCallback(function (string $class) use ($metadata): DTOMetadata {
                return $metadata[$class];
            });

        $meta = $factory->getDTOExcludeMetadata('standard', 'exclude');

        $names = ['one', 'two'];
        $this->assertEquals($names, array_keys($meta->propertyTypes));
    }

    /**
     * @test
     * @return void
     */
    public function make_extract_metadata_subset_of_props(): void
    {
        /**
         * @var Factory|MockObject $factory
         */
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getDTOMetadata'])
            ->getMock();

        $metadata = [
            'standard' => new DTOMetadata(DataTransferObject::class, [
                'one' => new Property($factory, 'one', [], [], false, null),
                'two' => new Property($factory, 'two', [], [], false, null),
                'three' => new Property($factory, 'three', [], [], false, null),
                'four' => new Property($factory, 'four', [], [], false, null),
            ], NONE),
            'extract' => new DTOMetadata(DataTransferObject::class, [
                'three' => new Property($factory, 'three', [], [], false, null),
                'four' => new Property($factory, 'four', [], [], false, null),
                'five' => new Property($factory, 'two', [], [], false, null),
            ], NONE),
        ];

        $factory->method('getDTOMetadata')
            ->willReturnCallback(function (string $class) use ($metadata): DTOMetadata {
                return $metadata[$class];
            });

        $meta = $factory->getDTOExtractMetadata('standard', 'extract');

        $names = ['three', 'four'];
        $this->assertEquals($names, array_keys($meta->propertyTypes));
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

        $this->assertEquals($expected, $this->factory->extractUseStatements($code));
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

        $this->assertEquals($expected, $this->factory->extractUseStatements($code));
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

        $this->assertEquals(['string'], $firstName->getTypes(NONE));
        $this->assertEquals(['null', 'string'], $lastName->getTypes(NONE));
        $this->assertEquals(['string[]'], $aliases->getTypes(NONE));
        $this->assertEquals(['string'], $aliases->getArrayTypes());
        $this->assertEquals(['null', 'Test\\TestingPhoneDto'], $phone->getTypes(NONE));
        $this->assertEquals(['null', 'string'], $email->getTypes(NONE));
        $this->assertEquals(['null', 'Test\\TestingAddressDto'], $address->getTypes(NONE));
        $this->assertEquals(['null', 'Test\\TestingAddressDto'], $postalAddress->getTypes(NONE));
        $this->assertEquals(['null', 'Test\\TestingAddressDto[]'], $otherAddresses->getTypes(NONE));
        $this->assertEquals(['string'], $status->getTypes(NONE));
    }

    /**
     * @test
     * @return void
     */
    public function can_map_simple_types(): void
    {
        $type = $this->factory->mapType('Phone', null, ['Phone' => 'Acme\\Test\\TestingPhoneDto']);

        $this->assertEquals('Acme\\Test\\TestingPhoneDto', $type);
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

        $this->assertEquals('Acme\\Test\\TestingPhoneDto', $type);
    }
}
