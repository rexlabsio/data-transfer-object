# data-transfer-object

[![Build Status](https://travis-ci.com/rexlabsio/data-transfer-object.svg?token=RUyjxjL2fH47cxZ6jUPh&branch=master)](https://travis-ci.com/rexlabsio/data-transfer-object)

## Overview

Use DataTransferObjects to map raw array data to strongly typed objects. The boundaries of many php applications send and receive associative arrays with no type safety. Adding typed objects in key locations adds stability and can show expose faulty assumptions about the shape of your data.

## Install

Via Composer

``` bash
composer require rexlabs/data-transfer-object
```

## Usage

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
 */
class UserDto extends DataTransferObject
{
}

$rawData = [
    'first_name' => 'James',
    'last_name' => 'Kirk',
    'email' => 'jim@starfleet.ufp',
    50,
];

$kirk = UserDto::make($rawData);
````

## Guide

Data transfer objects are useful in many contexts and have additional features for convenience and refactoring.  
Check the [guide](docs/SUMMARY.md) for details.

## Migrating from an older version?

Follow the [migration](docs/migration.md) guide.

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
