<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\TransferObjects;

use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * Class TestingAddressDto
 *
 * @property string $line_1
 * @property null|string $line_2
 * @property null|string $suburb
 * @property null|int $postcode
 */
class TestingAddressDto extends DataTransferObject
{
}
