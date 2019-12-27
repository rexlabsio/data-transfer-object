# data-transfer-object

[![Build Status](https://travis-ci.com/rexlabsio/data-transfer-object.svg?token=RUyjxjL2fH47cxZ6jUPh&branch=master)](https://travis-ci.com/rexlabsio/data-transfer-object)

## Overview

Data transfer objects with [typescript style](https://www.typescriptlang.org/docs/handbook/utility-types.html) utility type toggles.

## Install

Via Composer

``` bash
composer require rexlabs/data-transfer-object
```

## Usage

Define a DTO class using the phpdoc to specify the allowed types for properties. 
All properties are immutable by default but can be changed following the [advanced usage](docs/advanced_dto_usage.md).

```php
use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * @property string $first_name
 * @property null|string $last_name
 * @property string $email
 * @property null|int $age
 * @property null|MyDto $senior_officer
 */
class MyDto extends DataTransferObject
{
}
```

Then make instances of that type using valid property data.

```php
// Make a valid instance from raw data
$object = MyDto::make([
    'first_name' => 'James',
    'last_name' => 'Kirk',
    'email' => 'jim@starfleet.ufp',
    50,
]);

// Trying to assign an incorrect value to a property will fail with a TypeError
$object->first_name = []; // type error

// Attempting to create an instance with data missing will fail with a TypeError
$object = MyDto::make([
    'first_name' => 'James', // missing properties type error
]);

// Create with data missing is ok if properties have defaults eg null, false, []
$object = MyDto::make([
    'first_name' => 'James',
    'email' => null,
    50,
]);

$lastName = $object->last_name; // Default value null
isset($object->last_name); // last_name defaulted to null so `isset false`
isset($object->email); // email was set to null so `isset false`
isset($object->first_name); // first name has been set to a non null value so `isset true`

// `isDefined` can be more useful than `isset` to catch values that were set to null
$object->isDefined('first_name'); // first name was provided so `isDefined true`
$object->isDefined('last_name'); // last name was not provided so `isDefined false`
$object->isDefined('email'); // email was provided so `isDefined true` even though it is null
$object->isDefined('senior_officer.first_name'); // Is defined supports dot '.' notation for nested DTO values
```

See [advanced usage](docs/advanced_dto_usage.md) for flags and special utility types.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email lachlan.krautz@rexsoftware.com.au instead of using the issue tracker.

## Credits

- [Lachlan Krautz](https://github.com/lachlankrautz)
- [All Contributors](https://github.com/rexlabsio/data-transfer-object/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
