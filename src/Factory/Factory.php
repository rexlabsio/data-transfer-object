<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Factory;

use InvalidArgumentException;
use Rexlabs\DataTransferObject\ClassData\ClassDataProvider;
use Rexlabs\DataTransferObject\ClassData\ClassDataProviderContract;
use Rexlabs\DataTransferObject\ClassData\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Type\Casts\ArrayObjectPropertyCast;
use Rexlabs\DataTransferObject\Type\Casts\DataTransferObjectPropertyCast;
use Rexlabs\DataTransferObject\Type\PropertyCast;
use Rexlabs\DataTransferObject\Type\PropertyType;

use function array_key_exists;
use function sprintf;

use const Rexlabs\DataTransferObject\NONE;

/**
 * Class Factory
 *
 * @package Rexlabs\DataTransferObject
 */
class Factory implements FactoryContract
{
    /** @var ClassDataProviderContract */
    private $classDataProvider;

    /** @var DTOMetadata[] Keyed by class name */
    private $classMetadata;

    /** @var array */
    private $casts;

    /**
     * PropertyFactory constructor.
     *
     * @param ClassDataProviderContract $classDataProvider
     */
    public function __construct(ClassDataProviderContract $classDataProvider)
    {
        $this->classDataProvider = $classDataProvider;
        $this->classMetadata = [];
        $this->casts = [];
    }

    /**
     * @return static
     */
    public static function makeDefaultFactory(): self
    {
        $factory = new static(new ClassDataProvider());
        $factory->registerDefaultTypeCast(new DataTransferObjectPropertyCast());
        $factory->registerDefaultTypeCast(new ArrayObjectPropertyCast());
        return $factory;
    }

    /**
     * @param PropertyCast $cast
     *
     * @return void
     */
    public function registerDefaultTypeCast(PropertyCast $cast): void
    {
        $this->casts[] = $cast;
    }

    /**
     * Get DTOMetadata. Use a simple cache to ensure each class doc
     * is only parsed once
     *
     * @param string $class
     *
     * @return DTOMetadata
     */
    public function getClassMetadata(string $class): DTOMetadata
    {
        // Use cache if already created
        if (!array_key_exists($class, $this->classMetadata)) {
            $classData = $this->classDataProvider->getClassData($class);

            $this->setClassMetadata(
                $class,
                $classData->propertyTypesMap,
                $classData->defaults,
                $classData->propertyCastMap,
                $classData->baseFlags
            );
        }

        return $this->classMetadata[$class];
    }

    /**
     * @param string $class
     * @param string[][] $propertyTypesMap ['property_name' => ['null', 'int']]
     * @param mixed[] $propertyDefaultsMap ['property_name' => 'default_value']
     * @param PropertyCast[]|PropertyCast[][] $classPropertyCastMap
     *                                        either ['property_name' => PropertyCast]
     *                                        or ['property_name' => PropertyCast[]]
     * @param int $flags
     *
     * @return DTOMetadata
     */
    public function setClassMetadata(
        string $class,
        array $propertyTypesMap,
        array $propertyDefaultsMap = [],
        array $classPropertyCastMap = [],
        int $flags = NONE
    ): DTOMetadata {
        $propertyTypes = [];

        foreach ($propertyTypesMap as $propertyName => $typesForProperty) {
            $classPropertyCasts = $classPropertyCastMap[$propertyName] ?? [];

            $propertyTypes[$propertyName] = $this->makePropertyType(
                $class,
                $propertyName,
                $typesForProperty,
                $propertyDefaultsMap,
                // DTOs can define a single cast for a property or an array
                is_array($classPropertyCasts) ? $classPropertyCasts : [$classPropertyCasts]
            );
        }

        $this->classMetadata[$class] = new DTOMetadata(
            $class,
            $propertyTypes,
            $flags
        );

        return $this->classMetadata[$class];
    }

    /**
     * @param string $class
     * @param string $name
     * @param string[] $allTypes
     * @param string[] $propertyDefaultsMap
     * @param PropertyCast[] $classPropertyCasts
     *
     * @return PropertyType
     */
    private function makePropertyType(
        string $class,
        string $name,
        array $allTypes,
        array $propertyDefaultsMap = [],
        array $classPropertyCasts = []
    ): PropertyType {
        if (empty($allTypes)) {
            throw new InvalidArgumentException(sprintf(
                'At least one type must be defined for property: %s',
                $name
            ));
        }

        $singleTypes = [];
        $arrayTypes = [];
        $isNullable = false;
        $isArray = false;
        $isString = false;
        $isInt = false;
        $isBool = false;

        foreach ($allTypes as $type) {
            if ($type === 'null') {
                $isNullable = true;
            }

            if ($type === 'string') {
                $isString = true;
            }

            if ($type === 'int' || $type === PropertyType::TYPE_ALIASES['int']) {
                $isInt = true;
            }

            if ($type === 'bool' || $type === PropertyType::TYPE_ALIASES['bool']) {
                $isBool = true;
            }

            if ($type === 'array') {
                $isArray = true;
            }

            if (substr($type, -2) === '[]') {
                $arrayTypes[] = substr($type, 0, -2);
                $isArray = true;
            } else {
                $singleTypes[] = $type;
            }
        }

        // Put class property casts first before default factory casts
        // This will allow classes to define casts that will override the
        // global ones coming from the factory.
        /**
         * @var PropertyCast[] $availableCasts
         */
        $availableCasts = array_merge($classPropertyCasts, $this->casts);

        $typeCasts = [];
        $arrayTypeCasts = [];
        foreach ($availableCasts as $availableCast) {
            foreach ($singleTypes as $singleType) {
                if ($availableCast->canCastType($singleType)) {
                    $typeCasts[$singleType] = $availableCast;
                }
            }

            foreach ($arrayTypes as $arrayType) {
                if ($availableCast->canCastType($arrayType)) {
                    $arrayTypeCasts[$arrayType] = $availableCast;
                }
            }
        }

        // Order of default cascading is important
        // Lower checks will override higher ones
        // Generally preference is for the "least meaningful" value to win
        // eg null will override false or empty array
        $hasValidDefault = false;
        $default = null;

        if ($isArray) {
            $hasValidDefault = true;
            $default = [];
        }

        if ($isBool) {
            $hasValidDefault = true;
            $default = false;
        }

        if ($isNullable) {
            $hasValidDefault = true;
            $default = null;
        }

        // Class default last to override any implicit defaults
        if (array_key_exists($name, $propertyDefaultsMap)) {
            $hasValidDefault = true;

            // Would be better to type check default before creating property
            // type - chicken and egg problem
            $default = $propertyDefaultsMap[$name];
        }

        $propertyType = new PropertyType(
            $name,
            $singleTypes,
            $arrayTypes,
            $typeCasts,
            $arrayTypeCasts,
            $isNullable,
            $isString,
            $isInt,
            $hasValidDefault,
            $default
        );

        if ($hasValidDefault) {
            $defaultCheck = $propertyType->checkValue($default);
            if (!$defaultCheck->isValid()) {
                throw new InvalidTypeError($class, [$defaultCheck]);
            }
        }

        return $propertyType;
    }
}
