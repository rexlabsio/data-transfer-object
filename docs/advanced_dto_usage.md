# Advanced DataTransferObject Usage

Rexlabs DataTransferObject takes inspiration from Typescript [utility types](https://www.typescriptlang.org/docs/handbook/utility-types.html), allowing new types to be created based off other types.  
For php this allows several variations of a defined type from a single class definition.

## Flags

Flags can be passed to `::make` to allow alternate behaviour for instances.
Flags are autoloaded by composer and should be imported with `use const`.

```php
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\PARTIAL;

$object = MyDto::make($data, MUTABLE | PARTIAL);
```

Default flags for a class can be set by overriding the protected `defaultFlags` property.

```php
use Rexlabs\DataTransferObject\DataTransferObject;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NULLABLE;

/**
 * Class MyDto
 * 
 * @property string $first_name
 * @property null|string $last_name
 * @property string $email
 * @property int $age
 */
class MyDto extends DataTransferObject
{
    protected $defaultFlags = MUTABLE;
}

/**
 * Class MyContactDto
 * 
 * @property string $first_name
 * @property null|string $last_name
 */
class MyContactDto extends DataTransferObject
{
    protected $defaultFlags = NULLABLE | MUTABLE;
}
```

Available flags:

 - NONE
 - IGNORE_UNKNOWN_PROPERTIES
 - MUTABLE
 - ARRAY_DEFAULT_TO_EMPTY_ARRAY
 - NULLABLE_DEFAULT_TO_NULL
 - BOOL_DEFAULT_TO_FALSE
 - PARTIAL
 - NULLABLE
 - NOT_NULLABLE
 
#### NONE

No advanced behaviour; strict type rules apply.
 
#### IGNORE_UNKNOWN_PROPERTIES

By default unknown properties throw exceptions. Use this flag to simply ignore them.  
This flag can be useful when messy data is being received and only the filtered result is wanted.

#### MUTABLE

By default all object properties are immutable and will throw exceptions on write.
Use this flag to allow writes to all properties.

#### ARRAY_DEFAULT_TO_EMPTY_ARRAY

On by default. Properties with an array type will default to an empty array if omitted.

#### NULLABLE_DEFAULT_TO_NULL

On by default. Properties allowing a null type will default to null if omitted.

#### BOOL_DEFAULT_TO_FALSE

Properties allowing a bool type will default to false if omitted.

#### PARTIAL

No fields are required, objects will only validated properties that are provided.

#### NULLABLE

Override documented types, all fields become nullable.

#### NOT_NULLABLE

Override documented types, all fields are no longer nullable.
