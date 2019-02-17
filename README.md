# PAF
PAF (PHP API Framework) is a Framework to create simple API's through PHP and outputting them as JSON.

## Table of contents
- Setting up
- Example
- Instanciation
    - Methods
- Creating routes
    - Route
- Handling return values
    - Response

## Setting up
1. Download the `PAF.php` file, that includes the needed classes.
2. Import the file into your `index.php` file:
    ```php
    require_once 'path/to/PAF.php';
    ```
3. If you are using a Apache Webserver, you have to route all requests, that can not be found on the server, to your `index.php` file with a `.htaccess` file. Either use the one provided in this repository or add the following lines to your own:
    ```apacheconf
    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule . index.php [L]
    ```
4. Now you are ready to go

## Example
```php
<?php
    require_once 'path/to/PAF.php';

    $router = new PAF('/api', ['GET']);

    $router->setCorsEnabled(true);

    $router->map('GET', '/', function($request){
        return [
            "info" => "PAF test-api Version 1.0"
        ];
    });

    $router->map('GET', '/user/[i:id]', function($request){
        $user_id = $request['params']['id']; // integer

        [...] // get user from DB

        $response = new Response();

        if($error){
            $response->error();
            $response->value = -1;
        }else{
            $response->value = $user;
        }

        return $response;
    });

    $router->execute();
?>
```

This example contains two routes: `GET /` and `GET /user/id`.

By requesting `GET /` the following JSON-Object will be returned:
```json
{
    "info": "PAF test-api Version 1.0"
}
```

By requesting `GET /user/<id>` with `id` beeing a integer, either a JSON-Object beeing the user-object or a error code (-1) will be returned and the http-response-code will be set to 500 (see Response) // TODO:

## Instanciation
To create a new PAF-Object write the following line:
```php
$router = new PAF();
```
The constructor has two optional parameters:
```php
$router = new PAF($basePath, $allowedMethods);
```
`$basePath` is the path from where on the route will be matched<br>
`$allowedMethods` is an array with http-methods that are allowed for this instance

Example:
```php
$router = new PAF('/api', ['GET', 'POST', 'OPTIONS']);
```

### Methods
The PAF-Object has several methods:

#### set/getAllowedMethods
Sets/returns the allowed http-methods for this instance

*Default:* all from `PAF::getMethods()`

```php
$router->setAllowedMethods(['GET', 'POST', 'OPTIONS']);

print_r($router->getAllowedMethods()); // returns an array of strings
```

#### (static) getMethods
Returns all available http-methods for the framework

```php
print_r(PAF::getMethods()); // returns an array of strings
```

#### set/getCorsEnabled
Sets/returns if CORS should be enabled for this instance

*Default:* `false`

```php
$router->setCorsEnabled(true);

echo $router->getCorsEnabled(); // returns true in this case
```

#### set/getAuthorization
Sets/returns if a http-authorization-header will be set. If it is set to true, the target-function will recieve the header-value in the request-object

*Default:* `false`

```php
$router->setAuthorization(true);

echo $router->getAuthorization(); // returns true in this case
```

#### set/getBasePath
Sets/returns the base-path of the instance. The base-path is the path from where on the route should be matched.

For example: `https://example.com/api/load` and `basePath` is set to `/api` then it will match only `/load`

*Default:* `''`

```php
$router->setAuthorization(true);

echo $router->getAuthorization(); // returns true in this case
```

#### getRoutes
Returns all routes, that should be matched for this instance

*Default:* `[]`

```php
print_r($router->getRoutes());
```

#### getNamedRoutes
Returns the route with the name specified in the argument

```php
print_r($router->getNamedRoute('default'));
```

#### map
Adds a new route to the instance

`map(METHOD, PATH, TARGET, NAME)`

- `METHOD` is the http-method in caps
- `PATH` is the path that should be matched (see Path) // TODO:
- `TARGET` is the function that is executed when this route is matched. It is executed with a request-array as a argument (see Request) // TODO:
- `NAME` is the name of the route (optional)

```php
$router->map('GET', '/', function($request){
    return 'Success';
}, 'default');
```

#### addRoute
Does the same as `$router->map(...)`, but uses a Route-object instead (see Route) // TODO:

```php
$route = new Route('GET', '/', function($request){
    return 'Success';
}, 'default');

$router->addRoute($route);
```

#### execute
Has to be called, always when the router should do its job (after all routes have been declared). This method matches the route against all routes-path and chooses the first match. Then returns `true`, if a match has been found, otherwise `false`

Before the call to this function, no output to the document is allowed (for example with `echo`), otherwise there will be errors (because of setting headers)

```php
echo $router->execute();
```

## Creating routes
Routes can be created with either of the two following methods:
```php
$router->map(METHOD, PATH, TARGET, NAME);
```
```php
$route = new Route(METHOD, PATH, TARGET, NAME);
$router->addRoute($route);
```

### Route