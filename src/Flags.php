<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

const NONE                         = 0;

// Discard unknown properties
const IGNORE_UNKNOWN_PROPERTIES    = 1;

// Store unknown properties separately for debugging
const TRACK_UNKNOWN_PROPERTIES     = 1 << 1;

// Allow edits, objects are immutable by default
const MUTABLE                      = 1 << 2;

// Ignore requirements and defaults only filling what is provided
const PARTIAL                      = 1 << 3;

// Define missing props using default values
const DEFAULTS                     = 1 << 4;
