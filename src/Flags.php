<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

const NONE                         = 0;

const IGNORE_UNKNOWN_PROPERTIES    = 1;

// Allow edits, objects are immutable by default
const MUTABLE                      = 1 << 1;

const ARRAY_DEFAULT_TO_EMPTY_ARRAY = 1 << 2;

const NULLABLE_DEFAULT_TO_NULL     = 1 << 3;

// Ignore requirements and defaults only filling what is provided
const PARTIAL                      = 1 << 4;

// Override phpdoc types; make all properties nullable
const NULLABLE                     = 1 << 5;

// Override phpdoc types; make properties not nullable
const NOT_NULLABLE                 = 1 << 6;
