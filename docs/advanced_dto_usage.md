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

#### PARTIAL

No fields are required, objects will only validated properties that are provided.

#### NULLABLE

Override documented types, all fields become nullable.

#### NOT_NULLABLE

Override documented types, all fields are no longer nullable.
  
## Make Utility Types
  
Other utility types can be created with alternative `make` methods.
  
```php
// Make a type based off MyDto with only `first_name` and `age`
$subset = MyDto::makePick(['first_name', 'age'], $data);
```
  
Types:
  
 - Record
 - Pick
 - Omit
 - Exclude
 - Extract

## Make Utility Types
 
Other utility types can be created with alternative `make` methods.
 
```php
// Make a type based off MyDto with only `first_name` and `age`
$subset = MyDto::makePick(['first_name', 'age'], $data);
```
 
Types:
 
  - Record
  - Pick
  - Omit
  - Exclude
  - Extract

#### Record

Make a DTO where each named property is of `MyDto` type.

```php
$officers = MyDto::makeRecord(['captain', 'science_officer'], [
    'captain' => [
        'first_name' => 'James',
        'last_name' => 'Kirk',
    ],
    'science_officer' => [
        'first_name' => 'S\'chn',
        'last_name' => 'Spock',
    ],
]); // Type `DataTransferObject`

$officers->captain; // Type `MyDto`
$officers->science_officer; // Type `MyDto`
```

#### Pick

Make a DTO with only the picked properties

```php
$contact = MyDto::makePick(['first_name'], [
    'first_name' => 'James',
]); // Type `MyDto` with only the "first_name" property
```

#### Omit

Make a DTO without omitted properties

```php
$serviceRecord = MyDto::makeOmit(['first_name'], [
    'last_name' => 'Kirk',
    'email' => 'jim@starfleep.ufp',
    'age' => 50,
]); // Type `MyDto` with every property except "first_name"
```

#### Exclude

Make a DTO without all properties defined in other DTO class.

```php
$surveyData = MyDto::makeExclude(MyContactDto::class, [
    'email' => 'jim@starfleep.ufp',
    'age' => 50,
]); // Type `MyDto` with only "email" and "age" properties
```

#### Extract

Make a DTO with only properties defined in both DTO classes.

```php
$contact = MyDto::makeExtract(MyContactDto::class, [
    'first_name' => 'James',
    'last_name' => 'Kirk',
]); // Type `MyDto` with only "first_name" and "last_name" properties
```
