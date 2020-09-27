# Upgrading To 0.5.0 from 0.4.2

## Updating Dependencies

Update your `rexlabs/data-transfer-object` dependency to `0.5.0` in your composer.json file.

## Low Impact Changes

- Typescript Utility Types Removed
- `DataTransferObject::getProperties()` Deprecated

## Typescript Utility types removed

**Likelihood Of Impact: Low**

The many of the typescript inspired utility types have been removed. They were rarely used if ever. If you used them you'll need to refactor those classes without them.

- Removed `DataTransferObject::makeRecord()`
- Removed `DataTransferObject::makePick()`
- Removed `DataTransferObject::makeOmit()`
- Removed `DataTransferObject::makeExclude()`
- Removed `DataTransferObject::makeExtract()`

## DataTransferObject::getProperties() Deprecated

**Likelihood Of Impact: Low**

The `DataTransferObject::getProperties()` method has been deprecated and replaced with `DataTransferObject::getDefinedProperties()`. Please update your calls to this method accordingly.
