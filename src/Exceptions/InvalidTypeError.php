<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Exceptions;

use Rexlabs\DataTransferObject\PropertyTypeCheck;
use Throwable;

/**
 * Class InvalidTypeError
 *
 * @package Rexlabs\DataTransferObject\Exceptions
 */
class InvalidTypeError extends DataTransferObjectTypeError
{
    /** @var string */
    private $class;

    /** @var PropertyTypeCheck[] */
    private $typeChecks;

    /**
     * InvalidTypeError constructor.
     *
     * @param string $class
     * @param PropertyTypeCheck[] $typeChecks
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $class,
        array $typeChecks,
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(self::buildMessage($class, $typeChecks), $code, $previous);
        $this->class = $class;
        $this->typeChecks = $typeChecks;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return PropertyTypeCheck[]
     */
    public function getTypeChecks(): array
    {
        return $this->typeChecks;
    }

    /**
     * @param string $class
     * @param PropertyTypeCheck[] $typeChecks
     *
     * @return string
     */
    public static function buildMessage(
        string $class,
        array $typeChecks
    ): string {
        $classParts = explode('\\', $class);
        $shortClass = end($classParts);
        $plural = count($typeChecks) > 1;
        $typeCheckMessages = array_map([self::class, 'checkMessage'], $typeChecks);

        return sprintf(
            "Invalid type%s for %s: \n%s",
            $plural,
            $shortClass,
            implode("\n", $typeCheckMessages)
        );
    }

    /**
     * @param PropertyTypeCheck $check
     *
     * @return string
     */
    public static function checkMessage(PropertyTypeCheck $check): string
    {
        $value = $check->getValue();
        $types = $check->getTypes();

        /*
        if ($value === null) {
            $value = 'null';
        }

        if (is_object($value)) {
            $value = get_class($value);
        }

        if (is_array($value)) {
            $value = 'array';
        }
        */

        $expectedTypes = implode('|', $types) ?: 'none';
        $currentType = gettype($value);

        return sprintf(
            '%s - type "%s" is not assignable to type "%s"',
            $check->getName(),
            $currentType,
            $expectedTypes
        );
    }
}
