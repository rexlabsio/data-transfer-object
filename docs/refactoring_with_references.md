# Refactoring With References

## Simple Refactoring

Using DTOs you can replace many references to associative arrays with type objects. Many areas of the application that used to depend on string literal array indexes now use object property references.

```php
use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * @property string $first_name
 * @property string $last_name
 */
class UserData extends DataTransferObject
{
}

// Common php code relying on assoc arrays
function getFullNameFromData(array $userData): string
{
  return $userData['first_name'] . ' ' . $userData['last_name'];
}

// Type safe DTOs
function getFullNameFromUser(UserData $userData): string
{
  return $userData->first_name . ' ' . $userData->last_name;
}
```

In the above example because of the phpdoc specifying the property name for the type this code can easily be refactored in a modern IDE like "phpstorm", or a supped up text editor like "emacs". Renaming the property name on the class phpdoc will automatically rename all other references to that property; the `getFullNameFromUser` method would get the new property names for free.

## Problem

Some methods in the DTO library still rely on strings parameters to map to properties. If the get user name method needs to check defined properties it would pass a string literal. 

```php
// Before refactor
function getFullNameFromUser(UserData $userData): string
{
  if ($userData->isDefined('first_name') && $userData->isDefined('last_name')) {
    return $userData->first_name . ' ' . $userData->last_name;
  }

  return 'unknown';
}

// After failed refactor: 
// - first_name -> given_name
// - last_name -> surname
function ruinedGetFullNameFromUser(UserData $userData): string
{
  if ($userData->isDefined('first_name') && $userData->isDefined('last_name')) { // string literals still using the old names
    return $userData->given_name . ' ' . $userData->surname; // Property names automatically updated
  }

  return 'unknown';
}
```

The above rename refactoring fails; it renames the properties, but not the string passed to the is defined check. This silently introduces a bug where sometimes the method will throw an undefined exception when it used to return 'unknown'.

## Solution

DTOs provide reference classes that appear as the same class as the DTO to your IDE allowing code completion when typing property names. Reference classes use this feature to resolve a property to the string name of that property or check if there is a value defined for that property in a way that is compatible with an IDE's refactoring or other static analysis tools.

#### Property References

Property references can be obtained statically from the class name or from an instance. All it knows is the names of properties for that class and is used to resolve them in a way your IDE understands and can perform static analysis and refactoring on.

```php
$ref = UserData::ref(); // Obtain from static class

$user = UserData::make([]); 
$sameRef = $user::ref(); // Obtain from instance

$ref->first_name; // (string) "first_name", use anywhere you need the string name of the property
```

Using the property reference object we can rewrite the problem function without any string literals; so our solution is refactoring friendly.

```php
// Before refactor
function getFullNameFromUser(UserData $userData): string
{
  $ref = UserData::ref(); // $ref appears to the IDE as union type `PropertyReference|UserData`

  // $ref will have code completion as it was a `UserData` instance
  if ($userData->isDefined($ref->first_name) && $userData->isDefined($ref->last_name)) {
    return $userData->first_name . ' ' . $userData->last_name;
  }

  return 'unknown';
}

// After successful refactor: 
// - first_name -> given_name
// - last_name -> surname
function refactoredGetFullNameFromUser(UserData $userData): string
{
  $ref = UserData::ref(); // $ref appears to the IDE as union type `PropertyReference|UserData`

  if ($userData->isDefined($ref->given_name) && $userData->isDefined($ref->sur_name)) { // Property references automatically updated
    return $userData->given_name . ' ' . $userData->surname; // Property names automatically updated 
  }

  return 'unknown';
}
```

#### Is Defined Reference

`IsDefinedReference` objects also appear as the same class as the DTO they came from and provide code completion for property names. They can only be retrieved from an instance (never statically) since they will call `isDefined` on that instance.

They allow writing `isDefined` checks in a way that is refactoring friendly. Whereas the property reference returns the string value of the name of the property, `refIsDefined` property references all return a `bool` value signifying the status on the DTO instance.

```php
$user = UserData::make([]); 

if ($user->refIsDefined()->first_name) { // Is the property "first_name" for the instance $user defined
  return $user->first_name;
}
```

## Warnings

- There is a small amount of overhead in resolving a reference over using a string literal
- Overuse of these references may create too much clutter or mental overhead for you or your team members
- Your IDE will likely offer to code complete methods for your DTO on the reference class, this is misleading and may bother you - reference classes are only for properties
- You may not value code completion, refactoring or even use an IDE - this warning is fake; if this is you I can't believe you would look at this library in the first place, what are you doing here?

Alternatively you can get the same safety from having tests for your code instead. If using the reference classes are not ideal for your solution do not use them.
