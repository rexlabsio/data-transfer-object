<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\TransferObjects;

use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Tests\TransferObjects\Other\TestingPhoneDto as Phone;

/**
 * Class TestingPersonDto
 *
 * @property-read string $first_name
 * @property-read null|string $last_name
 * @property-read string[] $aliases
 * @property-read null|Phone $phone
 * @property-read null|string $email
 * @property-read null|TestingAddressDto $address
 * @property-read null|TestingAddressDto $postal_address
 * @property-read string $status
 */
class TestingPersonDto extends DataTransferObject
{
    protected static function getDefaults(): array
    {
        return [
            'status' => 'ready',
        ];
    }
}
