<?php

namespace Rexlabs\DataTransferObject;

use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;

class PropertyTypeCheck
{
    /** @var string */
    private $name;

    /** @var string[] */
    private $types;

    /** @var mixed */
    private $value;

    /** @var bool */
    private $valid;

    /**
     * TypeCheck constructor.
     *
     * @param string $name
     * @param string[] $types
     * @param mixed $value
     * @param bool $valid
     */
    public function __construct(string $name, array $types, $value, bool $valid)
    {
        $this->name = $name;
        $this->value = $value;
        $this->types = $types;
        $this->valid = $valid;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param string $prefixName
     *
     * @return self
     */
    public function getPrefix(string $prefixName): self
    {
        return new PropertyTypeCheck(
            $prefixName . '.' . $this->getName(),
            $this->getTypes(),
            $this->getValue(),
            $this->isValid()
        );
    }
}
