# Property Casting

DTO when configured will automatically cast raw data into known property types. DTOs use the cast system to automatically cast raw data into nested DataTransferObjects, data will be passed to the nested property class' make function with the same flags as the parent DTO.

## Custom Casts

Users can defined and enable their own casts for other common types that would be useful in a DTO.

### Creating a Custom Cast

Implement the PropertyCast interface do define which types to cast and how.

```php
use Rexlabs\DataTransferObject\Type\PropertyCast;

// Cast all string values to 'blue'
class AlwaysBlue implements PropertyCast
{
    /**
     * Each PropertyType's type is passed to `canCastType`. If any return true
     * then this PropertyCast will be attached to the PropertyType
     *
     * @param string $type
     *
     * @return bool
     */
    public function canCastType(string $type): bool
    {
        return $type === 'string';
    }

    /**
     * Map raw data to the cast type. If data is not in expected format it has
     * likely been cast to something else in a union type and should be ignored.
     * Simply return the data as is.
     *
     * @param string $name
     * @param mixed $data
     * @param string $type
     * @param int $flags
     *
     * @return mixed
     */
    public function toType(string $name, $data, string $type, int $flags = NONE)
    {
        // If not a string don't do the cast
        if (!is_string($data)) {
            return $data;
        }

        // Cast all strings to the string 'blue';
        return 'blue';
    }

    /**
     * Map type back to raw data. If property is not the expected type it has
     * likely been cast already and should be ignored.
     * Simply return the property as is.
     *
     * @param string $name
     * @param mixed $property
     * @param int $flags
     *
     * @return mixed
     */
    public function toData(string $name, $property, int $flags = NONE)
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
