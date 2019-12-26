<?php

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Feature\Examples;

use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * Class TestingNestableDto
 * @package Rexlabs\DataTransferObject\Tests\Feature\Examples
 *
 * @property string $id
 * @property string $first_name
 * @property null|string $last_name
 * @property null|TestingNestableDto $parent
 * @property null|\Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingNestableDto $partner
 * @property null|TestingNestableDto[] $siblings
 */
class TestingNestableDto extends DataTransferObject
{
}
