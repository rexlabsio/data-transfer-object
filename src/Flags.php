<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

const NONE                         = 0b00000000;

// Ignore requirements and defaults only filling what is provided
const PARTIAL                      = 0b00000001;

const IGNORE_UNKNOWN_PROPERTIES    = 0b00000010;

// Allow edits, objects are immutable by default
const MUTABLE                      = 0b00000100;

const ARRAY_DEFAULT_TO_EMPTY_ARRAY = 0b00001000;

const NULLABLE_DEFAULT_TO_NULL     = 0b00010000;
