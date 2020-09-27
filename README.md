# data-transfer-object

[![Build Status](https://travis-ci.com/rexlabsio/data-transfer-object.svg?token=RUyjxjL2fH47cxZ6jUPh&branch=master)](https://travis-ci.com/rexlabsio/data-transfer-object)

## Overview

Use DataTransferObjects to map raw array data to strongly typed objects. The boundaries of many php applications send and receive associative arrays with no type safety. Adding typed objects in key locations adds stability and can expose faulty assumptions about the shape of your data.

When dealing with data from user input, it often pays to know if a value has been defined as null or if it wasn't defined at all. DataTransferObjects make defined and undefined "a thing" for php.

DataTransferObject uses flags to change the default behaviour of your types making them useful for a variety of use cases.

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
```

## Guide

Data transfer objects are useful in many contexts and have additional features for convenience and refactoring.

Check the [guide](https://app.gitbook.com/@rexlabs/s/data-transfer-object/) for details.

## Upgrading from an older version?

Follow the [Upgrade Guide](docs/upgrading/upgrade_guide.md).

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
