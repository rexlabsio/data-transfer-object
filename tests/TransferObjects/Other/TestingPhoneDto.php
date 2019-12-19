<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\TransferObjects\Other;

use Rexlabs\DataTransferObject\DataTransferObject;

use const Rexlabs\DataTransferObject\NULLABLE_DEFAULT_TO_NULL;

/**
 * Class TestingAddressDto
 *
 * @property-read string $number
 * @property-read null|string $area
 */
class TestingPhoneDto extends DataTransferObject
{
    protected $defaultFlags = NULLABLE_DEFAULT_TO_NULL;
}
