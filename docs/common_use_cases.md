# Common Use Cases

DTOs are a practical helper to effectively extend php's type system. DTOs are abstract, they are low level, for this reason they can be useful in many domains and scenarios.

## Http APIs

APIs usually have a few edges, from the user input to the application, then from the application to the database and finally back to json again. Data needs to transform into different formats. APIs create new records with defaults or update exiting records while keeping existing values. Make these edges more secure by typing your data. The following is an example of how to use DTOs in a typical API application.

#### Assembler Pattern

Barely a pattern, an assembler is a class who's single responsibility is to map data from one type to another. Assemblers are useful to ensure classes that process business logic aren't cluttered with data transformations. 

```php
class UserDataFromHttpRequest
{
  public function assemble(Request $request, int $flags = NONE): UserData
  {
    // If property names or relationships differ between types perform mapping here
    return UserData::make($request->all(), $flags);
  }
}
```

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

#### Retrieve External API Data

Process:

 - Load record from database
 - Use assembler to map database data to DTO
 - Encode DTO to json with `toJson`
 - Send request to external API
 - Decode response from API to array data
 - Use assembler to map response data to DTO using flag:
   - `TRACK_UNKNOWN_PROPERTIES` if the upstream API has added new properties or changed in any way it pays to know about it
 - Check DTO for unknown properties and log them if found
