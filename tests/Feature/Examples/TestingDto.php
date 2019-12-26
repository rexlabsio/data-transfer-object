<?php

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Feature\Examples;

use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * Class TestingDto
 * @package Rexlabs\DataTransferObject\Tests\Feature\Examples
 *
 * @property string $id
 * @property string $first_name
 * @property null|string $last_name
 */
class TestingDto extends DataTransferObject
{
}
