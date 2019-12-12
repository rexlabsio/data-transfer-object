<?php

namespace Rexlabs\DataTransferObject\TransferObjects;

use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * Class TestingAddressDto
 * @package Rexlabs\DataTransferObject
 *
 * @property-read string $line_1
 * @property-read null|string $line_2
 * @property-read null|string $suburb
 * @property-read null|int $postcode
 */
class TestingAddressDto extends DataTransferObject
{
    protected static function getDefaults(): array
    {
        return [];
    }
}
