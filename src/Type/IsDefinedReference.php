<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Type;

use LogicException;
use Rexlabs\DataTransferObject\DataTransferObject;

class IsDefinedReference
{
    /** @var DataTransferObject */
    private $dto;

    /**
     * IsDefinedReference constructor.
     *
     * @param DataTransferObject $dto
     */
    public function __construct(DataTransferObject $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __get(string $name): bool
    {
        return $this->dto->isDefined($name);
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
