# Upgrading To 1.0.0-rc.1 from 0.5.0

## Updating Dependencies

Update your `rexlabs/data-transfer-object` dependency to `^1.0.0` in your composer.json file.

## High Impact Changes

- `DataTransferObject::getProperties()` Removed
- `DataTransferObject::$defaultFlags` Renamed
- `DataTransferObject::$baseFlags` Value Updated
- `PARTIAL` Undefined Property Access Now Throws
- Default values no longer defined implicitly

## Low Impact Changes

- Flags `NULLABLE` and `NOT_NULLABLE` Removed
- Exceptions renamed

## DataTransferObject::getProperties() Removed

**Likelihood Of Impact: High**

The `DataTransferObject::getProperties()` method has been removed and replaced with `DataTransferObject::getDefinedProperties()`. Please update your calls to this method accordingly.

## DataTransferObject::$defaultFlags Renamed

**Likelihood Of Impact: High**

`DataTransferObject::$defaultFlags` property has been renamed to `baseFlags`. Please update your classes accordingly.

## DataTransferObject::$defaultFlags/$baseFlags Value Updated

**Likelihood Of Impact: High**

`DataTransferObject::$defaultFlags/$baseFlags` property's default value has been updated from `NULLABLE_DEFAULT_TO_NULL|ARRAY_DEFAULT_TO_EMPTY_ARRAY` to `NONE`. 

Classes that overrode the value already don't need to be updated but should consider removing the override if it was set to `NONE`. Classes that relied on the default do not need to update.

## PARTIAL Undefined Property Access Now Throws

**Likelihood Of Impact: High**

Partial objects no longer return default values or null for undefined properties. Undefined property access now throws an exception. Update code that deals with partials and guard property access using `isDefined`.

## Default values no longer defined implicitly

**Likelihood Of Impact: High**

Partial objects remain the same but non partial objects used to have missing properties defined with default values. Carefully examine DTO creation to check for reliance on properties being defined by defaults.

## Flags NULLABLE and NOT_NULLABLE Removed

**Likelihood Of Impact: Low**

The `NULLABLE` and `NOT_NULLABLE` flags, and the corresponding functionality has been removed. Update any use of modified nullable or not nullable types accordingly.


- default values not used unless WITH_DEFAULTS, add WITH_DEFAULTS where needed

## Exceptions renamed

**Likelihood Of Impact: Low**

Exceptions have been renamed. Update references accordingly.

|0.5.0|1.0.0|
|---|---|
|DataTransferObjectError|DataTransferObjectTypeError|
|ImmutableError|ImmutableTypeError|
|InvalidFlagsException|*removed*|
|InvalidTypeError|*same*|
|UninitialisedPropertiesError|UndefinedPropertiesTypeError|
|UnknownPropertiesError|UnknownPropertiesTypeError|
