<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type;

use LogicException;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;

/**
 * Class PropertyReference
 *
 * @package Rexlabs\DataTransferObject\Type
 */
class PropertyReference
{
    /** @var string */
    private $class;

    /** @var string[] */
    private $names;

    /**
     * Ref constructor.
     *
     * @param string $class
     * @param bool[] $names ['name' => true]
     */
    public function __construct(string $class, array $names)
    {
        $this->class = $class;
        $this->names = $names;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function __get(string $name): string
    {
        if (!($this->names[$name] ?? false)) {
            throw new UnknownPropertiesTypeError($this->class, [$name]);
        }

        return $name;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     * @deprecated Not supported
     */
    public function __set(string $name, $value): void
    {
        throw new LogicException('Not supported');
    }

    /**
     * @param string $name
     *
     * @return void
     * @deprecated Not supported
     */
    public function __isset(string $name): void
    {
        throw new LogicException('Not supported');
    }
}
