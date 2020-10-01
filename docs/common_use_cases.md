# Common Use Cases

DTOs are a practical helper to effectively extend php's type system. DTOs are abstract, they are low level, and for this reason they can be useful in many domains and scenarios.

## Assembler Pattern

Barely a pattern, an assembler is a class who's single responsibility is to map data from one type to another. Assemblers are useful to ensure classes that process business logic aren't cluttered with data transformations. In return by keeping business logic out of the assembler you get a class with no side effects that is safe to use and reuse throughout the application.

```php
class UserDataFromHttpRequest
//
// extends MyAbstractAssembler
// 
// Make sure to think twice before creating a shared abstraction assemblers.
// The assemble method will rarely be able to be shared as the parameters 
// depend on what is being transformed. 
// Helper methods for common checks and transformations are fine. 
// Consider a trait instead of an abstract class if only behaviour not "type" 
// is being shared.
{
  /**
   * Map http request data to a UserData DTO.
   * 
   * If property names or relationships differ perform mapping here.
   *
   * @param Request $request Current Http request
   * @param int $flags Flags to use when making the new DTO instance
   *
   * @return UserData
   */
  public function assemble(Request $request, int $flags = NONE): UserData
  {
    $data = [];

    // Mapping Data!
    //
    // Keep assembler code simple. Make sure abstractions you introduce don't 
    // make it harder to see what is happening. The assembler should be where 
    // you can see exactly what is happening.
    //
    if ($request->has('property_name')) {
        // Make sure to leave properties undefined if the caller did not supply
        // them. It is the responsibility of the caller to supply the PARTIAL
        // flag if not all properties are present. The assembler just maps what
        // it can.
        //
        $data['property_name'] = $request->get('property_name');   
    }

    // Business Logic!
    //
    // Where possible avoid bringing business logic into the assembler
    // The assemblers responsibility is to map data, if changes need to be 
    // made to the data in response to business logic then that should happen
    // elsewhere.
    //
    // if ($this->service->valueHasBusinessLogicSignificance($request->get('value')) {
    //   $data['value'] = $this->service->processValue($request->get('value'));
    // }

    // Source Additional Data!
    // 
    // Sometimes not all the required data required for a valid DTO can be 
    // derived from the passed parameters, sometimes additional data has to be
    // loaded from the database or another source. In these cases avoid directly
    // coupling the assembler to those sources, instead make sure the assembler 
    // has a reference to an `interface` that provides the data. This helps keep
    // the assembler useful in multiple contexts, keeps it testable and lowers 
    // its surface area in your application.
    //
    // For example rather than call a global function to load additional data
    // from the database, require an `interface` `ExtraUserDataProvider` and 
    // call `getExtraUserData`.  
    //   - In tests `ExtraUserDataProvider` can be an anonymous class 
    //   - In the application `ExtraUserDataProvider` can be a wrapper for a
    //     database call or even another assembler
    //
    if ($request->has('extra_id')) {
      $data['extra_data'] = $this
        ->extraUserDataProvider
        ->getExtraUserData($request->get('extra_id'));
    }

    return UserData::make($data, $flags);
  }
}
```

## Http APIs

APIs usually have a few edges, from the user input to the application, then from the application to the database and finally back to json again. Data needs to transform into different formats. APIs create new records with defaults or update exiting records while keeping existing values. Make these edges more secure by typing your data. The following is an example of how to use DTOs in a typical API application.

#### Create Record Endpoint

Process:

 - Decode json to array
 - Validate data
 - Use assembler to map data to new DTO using flags: 
   - `WITH_DEFAULTS` if the user hasn't provided a value it should be set to the default
   - `IGNORE_UNKNOWN_PROPERTIES` if the user has provided additional junk data it is safe to ignore it
 - Pass dto to application layer to process business logic
 - Use assembler to map DTO to database friendly data
 - Persist data to database
 - Map dto to json for use response data

#### Update Record Endpoint

Process:

 - Decode json to array
 - Validate data
 - Use assembler to map data to new DTO using flags: 
   - `PARTIAL` if the user has omitted properties we should update only those specified
   - `IGNORE_UNKNOWN_PROPERTIES` if the user has provided additional junk data it is safe to ignore it
 - Pass dto to application layer to process business logic (make sure to call `assertDefined` or `isDefined` before checking individual property values)
 - Use assembler to map DTO to database friendly data (make sure to call `getDefinedProperties`)
 - Persist data to database
 - Load record from database
 - Use assembler to map database data to DTO (no flags needed as database should provide perfect data, if not we want to see an error)
 - Map dto to json for use response data

## External API Data

Process:

 - Load record from database
 - Use assembler to map database data to DTO
 - Encode DTO to json with `toJson`
 - Send request to external API
 - Decode response from API to array data
 - Use assembler to map response data to DTO using flag:
   - `TRACK_UNKNOWN_PROPERTIES` if the upstream API has added new properties or changed in any way it pays to know about it
 - Check DTO for unknown properties and log them if found
