<?php

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

declare(strict_types=1);

namespace Rexlabs\DataTransferObject\Tests\Support;

use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * Class ExampleDataTransferObject
 *
 * @package Rexlabs\DataTransferObject\Tests\Feature\Examples
 *
 * @property string $id
 * @property string $first_name
 * @property null|string $last_name
 * @property null|ExampleDataTransferObject $parent
 * @property null|\Rexlabs\DataTransferObject\Tests\Support\ExampleDataTransferObject $partner
 * @property null|ExampleDataTransferObject[] $siblings
 */
class ExampleDataTransferObject extends DataTransferObject
{
}
