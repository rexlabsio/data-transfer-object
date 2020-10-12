<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type;

use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;

class TypeErrorData
{
    /** @var string */
    private $class;
    /** @var array */
    private $invalidChecks;
    /** @var array */
    private $unknownProperties;
    /** @var array */
    private $undefined;

    /**
     * TypeErrorData constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->invalidChecks = [];
        $this->unknownProperties = [];
        $this->undefined = [];
    }

    /**
     * @param PropertyTypeCheck $check
     *
     * @return void
     */
    public function addInvalid(PropertyTypeCheck $check): void
    {
        $this->invalidChecks[] = $check;
    }

    /**
     * @param string $name
     * @param $value
     *
     * @return void
     */
    public function addUnknownValue(string $name, $value): void
    {
        $this->unknownProperties[$name] = $value;
    }

    /**
     * @param InvalidTypeError $exception
     * @param string|null $name
     *
     * @return void
     */
    public function mapInvalidTypeData(InvalidTypeError $exception, string $name): void
    {
        $this->class = $exception->getClass();

        foreach ($exception->getNestedTypeChecks($name) as $nestedCheck) {
            $this->invalidChecks[] = $nestedCheck;
        }
    }

    /**
     * @param UndefinedPropertiesTypeError $exception
     * @param string $name
     *
     * @return void
     */
    public function mapUndefinedData(UndefinedPropertiesTypeError $exception, string $name): void
    {
        $this->class = $exception->getClass();

        foreach ($exception->getNestedPropertyNames($name) as $nestedPropertyName) {
            $this->undefined[] = $nestedPropertyName;
        }
    }

    /**
     * @param UnknownPropertiesTypeError $exception
     * @param string $name
     *
     * @return void
     */
    public function mapUnknownData(UnknownPropertiesTypeError $exception, string $name): void
    {
        $this->class = $exception->getClass();

        foreach ($exception->getNestedPropertyNames($name) as $nestedPropertyName) {
            // Safe to use null and ignore value since exception will
            // only throw when unknown properties are not being tracked
            $this->unknownProperties[$nestedPropertyName] = null;
        }
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return bool
     */
    public function hasInvalidChecks(): bool
    {
        return count($this->invalidChecks) > 0;
    }

    /**
     * @return bool
     */
    public function hasUnknownProperties(): bool
    {
        return count($this->unknownProperties) > 0;
    }

    /**
     * @return bool
     */
    public function hasUndefinedProperties(): bool
    {
        return count($this->undefined) > 0;
    }

    /**
     * @return array
     */
    public function getInvalidChecks(): array
    {
        return $this->invalidChecks;
    }

    /**
     * @return array
     */
    public function getUnknownProperties(): array
    {
        return $this->unknownProperties;
    }

    /**
     * @return array
     */
    public function getUndefined(): array
    {
        return $this->undefined;
    }
}
