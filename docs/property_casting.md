# Property Casting

DTO when configured will automatically cast raw data into known property types. DTOs use the cast system to automatically cast raw data into nested DataTransferObjects, data will be passed to the nested property class' make function with the same flags as the parent DTO.

## Custom Casts

Users can defined and enable their own casts for other common types that would be useful in a DTO.

### Creating a Custom Cast

Implement the PropertyCast interface do define how and when data should be cast.

```php
use Rexlabs\DataTransferObject\Type\PropertyCast;

// Cast all string values to 'blue'
class AlwaysBlue implements PropertyCast
{
    // Can cast this type
    // If yes this PropertyCast will be attached to the class property and `shouldCastValue`
    // Will be called for each assigment to the property
    public function canCastType(string $type): bool
    {
        return $type === 'string';
    }

    // If yes for this value then `castToType` will be called immediately after
    public function shouldCastValue($value): bool
    {
        return true;
    }

    // Same again but for toArray
    public function shouldMapToData($property): bool
    {
        return true;
    }

    // Perform the cast
    public function castToType(string $name, $data, string $type, int $flags = NONE): string
    {
        // Cast all string to the string 'blue';
        return 'blue';
    }

    // Map the cast value back to raw data if needed
    public function toData(string $name, $property, int $flags = NONE): array
    {
        // Will already be blue
        return $property;
    }
}
```

### Registering a PropertyCast Globally

For common types like Carbon dates or Enums it may be preferable to have all DTO classes automatically cast those property values. For those types you can register the PropertyCast class with the `DataTransferObject::$factory` and it will apply to all standard DTOs. The casting of nested DataTransferObject properties uses PropertyCasts in this way.

```php
use Rexlabs\DataTransferObject\DataTransferObject;

// Resolve instance of cast
$alwaysBlue = new AlwaysBlue();

// Register with static DTO factory
DataTransferObject::getFactory()->registerDefaultTypeCast($alwaysBlue);

$dto = Dto::make(['color' => 'red']);
$dto->color; // 'blue'
```

### Registering a PropertyCast for Specific Properties

For less common casts that should not be applied generally you can map a cast to individual properties in the class definition.

```php
use Rexlabs\DataTransferObject\DataTransferObject;

/**
 * @property string $name
 * @property string $color
 */
class Paint extends DataTransferObject
{
  public static function getCasts(): array
  {
    return ['color' => new AlwaysBlue()];
  }
}

$paint = Paint::make([
  'name' => 'Red',
  'color' => 'red'
]);

$paint->name; // 'Red' Not cast
$paint->color; // 'blue' Cast
```

When multiple casts are present for a property only the first cast to return `shouldCast -> true` will attempt to cast. 
When global and individual casts are present, individual casts get called first.
