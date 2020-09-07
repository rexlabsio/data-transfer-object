# Migration Guide

## Breaking Changes

### Default flags property name changed

`defualtFlags` property on `DataTransferObject` was renamed to `baseFlags`.

All DTOs overriding "defaultFlags" need to update to override "baseFlags".

### Default / base flags value changed

`defaultFlags` value updated from `ARRAY_DEFAULT_TO_EMPTY_ARRAY | NULLABLE_DEFAULT_TO_NULL` to `NONE`.

Existing DTOs that had overridden `defaultFlags` to `NONE` do no need updating.

Existing DTOs inheriting the old `defaultFlags` 

// TODO update with better advice once default values has been reworked.
