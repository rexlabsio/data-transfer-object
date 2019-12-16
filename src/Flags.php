<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

/**
 * Class Flags
 * @package Rexlabs\DataTransferObject
 */
class Flags
{
    public const NONE                         = 0b00000000;

    // Ignore requirements and defaults only filling what is provided
    public const PARTIAL                      = 0b00000001;

    public const IGNORE_UNKNOWN_PROPERTIES    = 0b00000010;

    // Allow edits, objects are immutable by default
    public const MUTABLE                      = 0b00000100;

    public const ARRAY_DEFAULT_TO_EMPTY_ARRAY = 0b00001000;

    public const NULLABLE_DEFAULT_TO_NULL     = 0b00010000;
}
