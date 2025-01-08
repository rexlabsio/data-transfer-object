# Changelog

All notable changes to `data-transfer-object` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## 1.1.0 - 2025-01-09

- PHP 8.4 compatibility
- Tests moved to GitHub actions

## 1.0.0 - 2020-09-23

### Added
- WITH_DEFAULTS flag to explicitly request default values
- toArrayWithDefaults
- toJsonWithDefaults
- makeFromJson
- DataTransferObject::isDefined support for nested array, object and ArrayAccess
- Custom / default casting system
- Property and isDefined references
- DataTransferObject::assertUndefined DataTransferObject::assertOnlyDefined
- DataTransferObject::isUndefined

### Fixed
- Shortened stack trace for nested DTO exceptions
- Default flags renamed to base flags and default to NONE
- DTOs never set default values unless explicitly requested
- Simple default flags eg cast array to empty array moved to implicit behaviour
- Exception names and messages

### Removed
- Unused NULLABLE flag
- Unused NOT_NULLABLE flag
- Now implicit DEFAULT_NULL_TO_NULL
- Now implicit DEFAULT_ARRAY_TO_EMPTY_ARRAY
- Now implicit DEFAULT_BOOL_TO_FALSE
- Deprecated DataTransferObject::getProperties()

## 0.5.0 - 2020-09-21

### Added
- DataTransferObject::remake, remakeOnly, remakeExcept helper methods
- DataTransferObject::assertDefined
- Store unknown properties

### Deprecated
- DataTransferObject::getProperties() use getDefinedProperties or getPropertiesWithDefaults instead

### Removed
- makeRecord
- makePick
- makeOmit
- makeExclude
- makeExtract
