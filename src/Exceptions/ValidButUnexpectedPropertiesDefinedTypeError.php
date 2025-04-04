<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Throwable;

/**
 * Class UnexpectedlyDefinedPropertiesTypeError
 *
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class ValidButUnexpectedPropertiesDefinedTypeError extends DataTransferObjectTypeError
{
    /** @var string */
    private $class;

    /** @var string[] */
    private $propertyNames;

    /**
     * UndefinedPropertiesError constructor.
     *
     * @param string $class
     * @param array $propertyNames
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $class,
        array $propertyNames,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            self::buildMessage($class, $propertyNames),
            $code,
            $previous
        );
        $this->class = $class;
        $this->propertyNames = $propertyNames;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getNestedPropertyNames(string $name): array
    {
        $nestedPropertyNames = [];
        foreach ($this->propertyNames as $propertyName) {
            $nestedPropertyNames[] = $name . '.' . $propertyName;
        }
        return $nestedPropertyNames;
    }

    /**
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        return $this->propertyNames;
    }

    /**
     * @param string $class
     * @param array $propertyNames
     *
     * @return string
     */
    private static function buildMessage(string $class, array $propertyNames): string
    {
        $classParts = explode('\\', $class);
        $shortClass = end($classParts);
        $pluralProperty = count($propertyNames) === 1
            ? 'Property'
            : 'Properties';
        $properties = array_map(
            function ($propertyName): string {
                return ' - ' . $propertyName;
            },
            $propertyNames
        );

        return sprintf(
            "Valid, but unexpected %s defined for %s\n%s",
            $pluralProperty,
            $shortClass,
            implode("\n", $properties)
        );
    }
}
