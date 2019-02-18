# PAF
PAF (PHP API Framework) is a Framework to create simple API's through PHP and outputting them as JSON.<br>
It was inspired by [AltoRouter](https://github.com/dannyvankooten/AltoRouter)

## Table of contents
- [Setting up](#setting-up)
- [Example](#example)
- [Instanciation](#instanciation)
    - [Methods](#methods)
- [Creating routes](#creating-routes)
    - [Route](#route)
    - [Request](#request)
- [Handling return values](#handling-return-values)
    - [Response](#response)
- [Catch unmapped routes](#catch-unmapped-routes)

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

By requesting `GET /user/<id>` with `id` beeing a integer, either a JSON-Object beeing the user-object or a error code (-1) will be returned and the http-response-code will be set to 500 (see [Response](#response))

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

- `METHOD` is the http-method in caps, or a wildcard-character (`*`) for any method
- `PATH` is the path that should be matched (see [Path](#path))
- `TARGET` is the function that is executed when this route is matched. It is executed with a request-array as a argument (see [Request](#request))
- `NAME` is the name of the route (optional)

```php
$router->map('GET', '/', function($request){
    return 'Success';
}, 'default');
```

#### addRoute
Does the same as `$router->map(...)`, but uses a Route-object instead (see [Route](#route))

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
This class defines a route:
```php
$route = new Route(METHOD, PATH, TARGET, NAME);
```

- `METHOD` is the http-method in caps, or a wildcard-character (`*`) for any method
- `PATH` is the path that should be matched (see [Path](#path))
- `TARGET` is the function that is executed when this route is matched. It is executed with a request-array as a argument (see [Request](#request))
- `NAME` is the name of the route (optional)

#### Path
Path is a string. There are three types of paths:
1. *static:* `/users`
2. *dynamic:* `/users/[i:id]`
3. *wildcard:* `*`

*Dynamic* paths contain at least one parameter. Parameters are defined as following:
`[type:name]`<br>
Type can be:
- `'*'`, `''` for *any type*
- `'s'` for a *string*
- `'i'` for a *integer*
- `'n'` for a *number*

The parameters will be contained in the request-array of the matched-routes target-function (see [Request](#request))

### Request
This array will be passed on to the matched-routes target-function as a parameter.

```php
[...]

$router->map('GET', '/', function($request){
    [...]
    return null;
});
```

It contains the following items:
```php
[
    'route' => Route Object,
    'method' => string,
    'path' => string,
    'params' => [],
]
```

The params array contains all parameters of this route with their name as the key:
```php
[
    [...],
    'params' => [
        'id' => 10
    ]
]
```

If authorization is enabled for this instance, the request-array also contains the key authorization with the content of the authorization header:
```php
[
    [...],
    'authorization' => '202cb962ac59075b964b07152d234b70'
]
```

## Handling return values
The target-function of the matched route has to return a value, that is displayed. It can be either a `Response`-Object or anything else.

If it is an object (other than `Response`) and has a function called
`toJSON()`, it will be executed and the output of that function will be used, otherwise the returned value will be encoded to JSON with `json_encode(...)`.

```php
[...]

class User{
    private $name = null;

    [...]

    function toJSON(){
        return [
            "name" => $this->name
        ];
    }
}

[...]

$router->map('GET', '/user', function(){
    return new User('Foo'); // toJSON() will be executed
});
```

If it is a `Response`-Object, its value-property will be used and the http-response-code will be set to its code-property (see [Response](#response)).

### Response
This object can be returned by the target-function of a matched route
```php
$response = new Response(VALUE, CODE);
```

- `VALUE` is the return value, that should be displayed
- `CODE` is the http-response-code for the request (see https://httpstatuses.com/)

These two properties are public member variables, so they can be changed very easily.

There are also some methods to set the code:
- `ok()` -> 200
- `created()` -> 201
- `badRequest()` -> 400
- `unauthorized()` -> 401
- `forbidden()` -> 403
- `notFound()` -> 404
- `methodNotAllowed()` -> 405
- `conflict()` -> 409
- `error()` -> 500
- `notImplemented()` -> 501

```php
[...]

$router->map('GET', '/user', function(){
    return new Response(null, 500);
});
```

## Catch unmapped routes
By setting the http-method and the path to `*`, every route will be matched. Only add this as the last route, to catch unmapped routes!

```php
[...]

$router->map('*', '*', function(){
    $response = new Response(null);
    return $response->badRequest();
});
```