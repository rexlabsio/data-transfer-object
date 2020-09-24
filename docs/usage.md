## Defining DTOs

Define a DTO class using the phpdoc to specify the allowed types for properties. 

```php
use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * @property string $first_name
 * @property null|string $last_name
 * @property string $email
 * @property null|int $age
 * @property null|UserDto $parent
 * @property UserDto[] $children
 * @property \Fully\Qualified\UserDto[] $siblings
 */
class UserDto extends DataTransferObject
{
}
```

Properties can be unions eg `null|string` and even imported class references - no need to use fully qualified class names.

The DTO factory parses the phpdoc once then the results cached so many DTOs can be instantiated without a performance cost.

#### Definition limitations

DTOs currently do not support properties through extension. DTO factory parses only the phpdoc for the concrete class.

## Making DTO Instances

Create instances from an array or json data using `DataTransferObject::make()`

Making a DTO without defining each property will throw `UndefinedPropertiesTypeError`. This ensures that each instance is valid and safe for use.

```php
use const Rexlabs\DataTransferObject\NONE;

$rawData = [
    'first_name' => 'James',
    'last_name' => 'Kirk',
    'email' => 'jim@starfleet.ufp',
    'age' => 34,
    'parent' => null,
    'children' => [],
    'siblings' => [],
];
$kirk = UserDto::make($rawData, NONE);

$json = '{
  "first_name": "S\'chn T\'gai", 
  "last_name": "Spock",
  "email" => "spock@starfleet.ufp",
  "age" => 35,
  "parent" => null,
  "children" => [],
  "siblings" => [],
}';
$spock = UserDto::makeFromJson($json, NONE);
```

## Using DTO Instances

DTO behave like ordinary models except that their property types are reliable and incorrect usage will throw type errors.

```php
$kirk->first_name; // 'James'

$kirk->spaceship; // Throws `UnknownPropertiesTypeError`

$kirk->first_name = 'Scotty'; // `Throw ImmutableTypeError`

$props = $kirk->getDefinedProperties(); // Get all properties currently on the model

$allProps = $kirk->getPropertiesWithDefaults(); // Get all properties and fill in missing with defaults
```

## Flags

Make functions take an array of raw data to process and map to type DTOs and as a second param any FLAGS to modify the default behaviour.

Available Flags:

 - `NONE`: the default param makes no changes
 - `IGNORE_UNKNOWN_PROPERTIES`: allows a DTO to be created with additional unknown properties
 - `TRACK_UNKNOWN_PROPERTIES`: tracks those properties for logging / debugging
 - `MUTABLE`: allow the DTO to be mutated (they are immutable by default)
 - `PARTIAL`: make with missing properties without throwing an error
 - `WITH_DEFAULTS`: replace missing values with valid defaults where possible
 
Flags can be combined as needed for example:

```php
use const Rexlabs\DataTransferObject\PARTIAL;
use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\WITH_DEFAULTS;
use const Rexlabs\DataTransferObject\TRACK_UNKNOWN_PROPERTIES;

$kirk = UserDto::make($rawData, PARTIAL | MUTABLE | WITH_DEFAULTS | TRACK_UNKNOWN_PROPERTIES);
```

DTOs merge flags passed to make with the base flags defined on the class. Override the `baseFlags` property on your DTO class to set flags that will be active on every instance.

```php
use Rexlabs\DataTransferObject\DataTransferObject;

use const Rexlabs\DataTransferObject\WITH_DEFAULTS;
use const Rexlabs\DataTransferObject\PARTIAL;

class UserDto extends DataTransferObject
{
  protected $baseFlags = WITH_DEFAULTS | PARTIAL; // All instances made of this class with have these flags set
}
```

## Partially Defined Objects

DTOs created with the PARTIAL flag do not require any properties to be defined. This can be useful when mapping user requests for updates where a model exists and only new changes need to be persisted eg a PATCH endpoint.

Example workflow:

```php
use const Rexlabs\DataTransferObject\PARTIAL;

public function handleRequest(array $data): array
{
  // Make partial using only keys actually set in $data
  $user = UserDto::make($data, PARTIAL);
 
  // Update the database with only values that were defined in the update
  $this->updateUser($user);
}
```

#### Caution

When working with partial DTOs directly accessing properties can dangerous. Consider a PATCH endpoint that mistakenly sets the user's email address to `null` because the user didn't define it.

For this reason direct access to an undefined property will throw `UndefinedPropertiesTypeError`

```php
use const Rexlabs\DataTransferObject\PARTIAL;

$partialData = [
    'first_name' => 'James',
    'last_name' => 'Kirk',
];
$partialKirk = UserDto::make($partialData, PARTIAL);

$partialKirk->first_name; // 'James'

$partialKirk->age; // Throw UndefinedPropertiesTypeError
```

When dealing with `PARTIAL` types make sure to spend the additional effort to check values.

```php
if ($partialKirk->isDefined('age')) { // false
  $partialKirk->age; // Only reached if the value was defined
}
```

Or just pass along only those values that are safe.

```php
return $partialKirk->getDefinedProperties();
```

It can also be worthwhile to logically ensure that all DTOs in a particular part of the application have certain properties defined. Call `assertDefined` at the top of the method for all services that require certain properties. Assert defined will throw to ensure that there are no hidden bugs in rare control flow paths eg.

```php
public function handle(UserDto $maybeKirk): void
{
  // Ensure age is always present even though it's only sometimes checked
  // This way the application knows straight away that it's always needed
  $maybeKirk->assertDefined(['first_name', 'last_name', 'age']);

  if ($this->sometimes()) {
    $this->checkAge($maybeKirk->age);
  }
}
```

## Additional Unknown Properties

DTOs will throw if created with property names that are not recognised. When creating DTOs from user input it would be cumbersome to first filter out all properties that don't belong to the DTO. Use either `IGNORE_UNKNOWN_PROPERTIES` or `TRACK_UNKNOWN_PROPERTIES` flags when creating an instance to allow them.

```php
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\IGNORE_UNKNOWN_PROPERTIES;
use const Rexlabs\DataTransferObject\TRACK_UNKNOWN_PROPERTIES;

// Make an instance with additional properties and no flags
$kirkWithExtras = UserDto::make($dataWithExtras, NONE); // Will throw unknown properties type error
$extraUnknownProps = $kirkWithExtras->getUnknownProperties(); // Empty array []

// Make an instance with additional properties and the ignore flag
$kirkWithExtras = UserDto::make($dataWithExtras, IGNORE_UNKNOWN_PROPERTIES); // Will ignore the extra props
$extraUnknownProps = $kirkWithExtras->getUnknownProperties(); // Empty array []

// Make an instance with additional properties and the track flag
$kirkWithExtras = UserDto::make($dataWithExtras, TRACK_UNKNOWN_PROPERTIES); // Will store those props separately on the model for logging / debugging
$extraUnknownProps = $kirkWithExtras->getUnknownProperties(); // All extra props []
```

## Default Property Values

DTO classes can define default values for properties. DTOs will only define default values for missing properties when using WITH_DEFAULTS flag. Alternatively call `getPropertiesWithDefaults` to get all properties including defaults even when the instance does not have the defaults defined.

DTO provides implicit defaults for simple types:

- nullable types will default to null
- array types will default to an empty array
- boolean types will default to false

Again these defaults will only be defined when you create the model with the `WITH_DEFAULTS` flag or when getting properties using `getPropertiesWithDefaults`.

Override the `getDefaults` method on your class to provide default values for properties.

```php
use Rexlabs\DataTransferObject\DataTransferObject;

use const Rexlabs\DataTransferObject\WITH_DEFAULTS;
use const Rexlabs\DataTransferObject\PARTIAL;

/**
 * @property string $first_name
 * @property null|string $last_name
 * @property string $email
 * @property null|int $age
 * @property bool $is_active
 * @property null|UserDto $parent
 * @property UserDto[] $children
 * @property \Fully\Qualified\UserDto[] $siblings
 */
class UserDto extends DataTransferObject
{
  protected static function getDefaults(): array
  {
    return [
      'email' => 'complaints@starfleet.ufp',
      'age' => 0,
    ];
  }
}

$lazyData = [
  'first_name' => 'Barkley',
];

$lazyUser = UserDto::make($lazyData, WITH_DEFAULTS); // Defines default values

$lazyUser->first_name; // Barkley
$lazyUser->last_name; // Implicitly defaults to null
$lazyUser->email; // Custom default set to complaints@starfleet.ufp
$lazyUser->age; // Custom default set to 0
$lazyUser->is_active; // Implicitly defaults to false
$lazyUser->parent; // Implicitly defaults to null
$lazyUser->children; // Implicitly defaults to []
$lazyUser->siblings; // Implicitly defaults to []

$partialUser = UserDto::make($lazyData, PARTIAL); // Allow undefined values, does not define defaults

$definedProperties = $partialUser->getDefinedProperties();
// [
//   'first_name' => 'Barkley',
// ]

$propertiesWithDefaults = $partialUser->getPropertiesWithDefaults();
// [
//   'first_name' => 'Barkley',
//   'last_name' => null,
//   'email' => 'complaints@starfleet.ufp',
//   'age' => 0,
//   'is_active' => false,
//   'parent' => null,
//   'children' => [],
//   'siblings' => [],
// ]
```

## Mutating Data

DTOs are immutable by default; they will throw if you attempt to modify their properties. Create with the `MUTABLE` flag to enable writes.

```php
use const Rexlabs\DataTransferObject\MUTABLE;

$immutableUser = UserDto::make($data);
$immutableUser->first_name = 'new name'; // Throws immutable type error

$mutableUser = UserDto::make($data, MUTABLE);
$mutableUser->first_name = 'new name'; // Sets the new value. Does not throw.
```

DTOs are immutable by default to encourage more functional style code. Limiting the number of times and places in your codebase mutate values reduces the surface area for bugs and can lead to clearer designs.

For workflows that require instances created with mostly the same properties or maybe a single mutation, rather than making the instance `MUTABLE` you can use helper methods to `remake` instances based off existing ones. Use remake helpers to make slightly modified copies of existing instances. Remade objects share the flags of the subject instance unless you specify replacements.

```php
$immutableUser = UserDto::make($data);

// Remake with all properties the same except for the age
$olderUser = $immutableUser->remake([
  'age' => $immutableUser->age + 1,
]);

// Remake only copying some properties
$onlyUser = $immutableUser->remakeOnly(
  // Copy all these props
  [
    'email',
    'age',
    'parent',
    'children',
    'siblings',
  ], 
  // Add these values for other prop names
  [
    'first_name' => 'Someone',
    'last_name' => 'Else'
  ]
);

// Remake only copying some properties
$exceptUser = $immutableUser->remakeExcept(
  // Copy everything except these props
  [
    'email',
    'age',
  ], 
  // Fill in missing props
  [
    'email' => 'someone@starfleet.ufp',
    'age' => 100,
  ]
);
```

#### Limitations

Although DTOs prevent the assignment of new values to properties it cannot stop mutations of those properties themselves. 

For example a DTO has a carbon `start_date` property, you cannot assign a new value to `start_date` but you can call methods on the carbon object that modify its internal state.

```php
$dto = DTO::make($data);

$dto->start_date = new Carbon($date); // Throws immutable type error
$dto->start_date->addHours(2); // Adds two hours to the date without assigning a new value to the property. Does not throw.
```

If you want to prevent mutation of the assigned objects, try using immutable alternatives instead e.g. the Carbon package provides CarbonImmutable
