<?php

namespace Rexlabs\DataTransferObject\TransferObjects\Other;

use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * Class TestingAddressDto
 * @package Rexlabs\DataTransferObject
 *
 * @property-read string $number
 * @property-read null|string $area
 */
class TestingPhoneDto extends DataTransferObject
{
    protected static function getDefaults(): array
    {
        return [];
    }
}
