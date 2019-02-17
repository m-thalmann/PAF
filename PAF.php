<?php
    /**
     * PAF (PHP API Framework) is a Framework to create simple API's
     * through PHP and outputting them as JSON.
     * 
     * @see {@link https://github.com/m-thalmann/PAF}
     */

    /**
     * This class is the main class to use the PHP API Framework.
     * It handels the routing and matching of the routes
     * 
     * @license MIT
     * @author Matthias Thalmann
     */
    class PAF {
        /**
         * @var array Array of all available http-methods
         */
        private static $METHODS = [
            'GET',
            'PUT',
            'POST',
            'DELETE',
            'HEAD',
            'OPTIONS'
        ];

        /**
         * @var array Array of all allowed http-methods for this instance
         */
        private $allowedMethods = null;

        /**
         * @var bool Boolean whitch defines if CORS should be enabled for this instance
         */
        private $cors_enabled = false;

        /**
         * @var bool Boolean whitch defines if a http-authorization-header will be set,
         *  and if it's set, it will be passed on to the target function
         */
        private $authorization = false;

        /**
         * @var array Array of all routes for this instance
         */
        private $routes = array();

        /**
         * @var array Array of the positions of the named-routes in the routes-Array
         */
        private $namedRoutes = array();

        /**
         * @var string String of the path, from where on the route will be matched
         *  for example: https://example.com/api/load and basePath is set to /api then it will
         *  match only /load
         */
        private $basePath = '';

        /**
         * Creates PAF-Router
         * 
         * @param string $basePath
         * @param array $allowedMethods
         */
        public function __construct($basePath = '', $allowedMethods = null) {
            $this->setBasePath($basePath);
            $this->setAllowedMethods($allowedMethods);            
        }

        /**
         * Sets the allowed Methods for this instance
         * 
         * @param array $allowedMethods
         * @return void
         */
        public function setAllowedMethods($allowedMethods){
            if(empty($allowedMethods)){
                $this->allowedMethods = PAF::$METHODS;
            }else{
                if(!is_array($allowedMethods)){
                    throw new Exception('Allowed methods must be array');
                }

                $this->allowedMethods = $allowedMethods;
            }
        }

        /**
         * Returns the allowed Methods for this instance
         * 
         * @return array All allowed Methods
         */
        public function getAllowedMethods(){
            return $this->allowedMethods;
        }

        /**
         * Returns the available Methods
         * 
         * @return array All available Methods
         */
        public static function getMethods(){
            return PAF::$METHODS;
        }

        /**
         * Checks the type of value according to $type:
         * - '*' -> any type
         * - ''  -> alias for '*'
         * - 's' -> string
         * - 'i' -> integer
         * - 'n' -> number
         * 
         * @param string $type
         * @param string $value
         * @return mixed false if the type does not match or converts value to type and returns it
         */
        private static function convertParam($type, $value){
            if($type == '*' || $type == 's' || $type == ''){
                return $value;
            }else if($type == 'i'){
                if(ctype_digit($value)){
                    return intval($value);
                }
            }else if($type == 'n'){
                if(is_numeric($value)){
                    return doubleval($value);
                }
            }
            return false;
        }
        
        /**
         * Sets if CORS is enabled for this instance
         * 
         * @param bool $enabled
         * @return void
         */
        public function setCorsEnabled($enabled){
            $this->cors_enabled = !!$enabled;
        }

        /**
         * Returns if CORS is enabled for this instance
         * 
         * @return bool
         */
        public function getCorsEnabled(){
            return $this->cors_enabled;
        }

        /**
         * Sets if a http-authorization-header will be set and returned
         * 
         * @param bool $enabled
         * @return void
         */
        public function setAuthorization($enabled){
            $this->authorization = !!$enabled;
        }

        /**
         * Returns if a http-authorization-header will be set
         * 
         * @return bool
         */
        public function getAuthorization(){
            return $this->authorization;
        }

        /**
         * Returns all routes that will be matched by this instance
         * 
         * @return array All routes
         */
        public function getRoutes() {
            return $this->routes;
        }

        /**
         * Returns the route named $name
         * 
         * @param string $name
         * @return Route The route named $name or null if not found
         */
        public function getNamedRoute($name){
            if(empty($name) || !is_string($name)){
                return null;
            }

            if(array_key_exists($name, $this->namedRoutes)){
                return $this->routes[$this->namedRoutes[$name]];
            }
        }

        /**
         * Adds a new Route to the routes-array
         * 
         * @param string $method
         * @param string $route
         * @param callable $target
         * @param string $name (optional)
         * @throws Exception
         * @return void
         */
        public function map($method, $route, $target, $name = null) {
            $this->addRoute(new Route($method, $route, $target, $name));
        }
        
        /**
         * Adds a new Route to the routes-array
         * 
         * @param Route $route
         * @throws Exception
         * @return void
         */
        public function addRoute($route){
            if($route instanceof Route && $route->verify($this->allowedMethods)){
                if($route->getName()){
                    if(array_key_exists($route->getName(), $this->namedRoutes)){
                        throw new Exception('Name already used');
                    }
                }

                $this->routes[] = $route;

                if($route->getName()){
                    $this->namedRoutes[$route->getName()] = max(array_keys($this->routes));
                }
            }else{
                throw new Exception('Route must be Route and be valid');
            }
        }

        /**
         * Sets the base path for this instance
         * 
         * @param string $path
         * @return void
         */
        public function setBasePath($path = '') {
            if(is_string($path)){
                $this->basePath = strtolower($path);
            }else{
                throw new Exception('Path must be string');
            }
        }

        /**
         * Returns the base path for this instance
         * 
         * @return string The base path
         */
        public function getBasePath() {
            return $this->basePath;
        }

        /**
         * Matches the request-url with the routes. The first successfull match
         * will be used and executed
         * 
         * @return bool True if a match was found otherwise false
         */
        public function execute() {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            
            if(substr($requestUrl, 0, strlen($this->basePath)) == $this->basePath){
                $requestUrl = substr($requestUrl, strlen($this->basePath));

                $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

                $request = [
                    'route' => null,
                    'method' => $requestMethod,
                    'path' => $requestUrl,
                    'params' => [],
                ];

                if($this->authorization){
                    $request['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
                }

                foreach($this->routes as $route){
                    $method = $route->getMethod();
                    $path = $route->getPath();
                    $target = $route->getTarget();
                    $name = $route->getName();

                    $request['route'] = $route;

                    if($method == '*' || $method == $requestMethod){
                        if($path == '*'){
                            return true;
                            $this->resolve($target($request));
                        }else{
                            $requestSegments = explode('/', $requestUrl);
                            $pathSegments = explode('/', $path);

                            if($requestSegments[count($requestSegments)-1] == ''){
                                unset($requestSegments[count($requestSegments)-1]);
                            }
                            if($pathSegments[count($pathSegments)-1] == ''){
                                unset($pathSegments[count($pathSegments)-1]);
                            }

                            if(count($requestSegments) == count($pathSegments)){
                                $ok = true;
                                $params = [];

                                for($i = 0; $i < count($requestSegments) && $ok; $i++){
                                    $requestSegment = $requestSegments[$i];
                                    $pathSegment = $pathSegments[$i];

                                    if(substr($pathSegment, 0, 1) == '[' && substr($pathSegment, count($pathSegment) -2) == ']'){
                                        list($paramType, $paramName) = explode(':', substr($pathSegment, 1, count($pathSegment) -2));

                                        $convertedParam = PAF::convertParam($paramType, $requestSegment);

                                        if($convertedParam === false){
                                            $ok = false;
                                        }else{
                                            $params[$paramName] = $convertedParam;
                                        }
                                    }else if($pathSegment != '*' && $pathSegment != $requestSegment){
                                        $ok = false;
                                    }
                                }

                                if($ok){
                                    $request['params'] = $params;
                                    $this->resolve($target($request));
                                    return true;
                                }
                            }
                        }
                    }
                }

                return false;
            }else{
                return false;
            }
        }

        /**
         * Outputs the result of the match of execute and sets custom headers.
         * If the return value of $ret is a object, and it has the function toJSON,
         * the return value will be set to $ret->toJSON(). The function should return
         * either an object with public member variables or a array with keys
         * 
         * @param mixed $ret The result that should be displayed or a Response-Object with custom information
         * @return void
         */
        private function resolve($ret){
            header('Content-Type: application/json');

            $allowedMethods = "";

            for($i = 0; $i < count($this->allowedMethods); $i++){
                $allowedMethods .= $this->allowedMethods[$i];

                if($i < count($this->allowedMethods)-1){
                    $allowedMethods .= ', ';
                }
            }

            header("Access-Control-Allow-Methods: " . $allowedMethods);

            if($this->cors_enabled){
                header("Access-Control-Allow-Origin: *");
            }
            
            $value = null;
            $code = 200;

            if($ret instanceof Response){
                $value = $ret->value;
                $code = $ret->code;
            }else{
                $value = $ret;
            }

            if(is_object($value)){
                if(method_exists($value, 'toJSON')){
                    $value = $value->toJSON();
                }
            }

            http_response_code($code);

            echo json_encode($value);
        }
    }

    /**
     * This class defines a route
     * 
     * @license MIT
     * @author Matthias Thalmann
     */
    class Route {
        /**
         * @var string The method this route should match. For any method use '*'
         */
        private $method = null;

        /**
         * @var string The path this route should match.
         *  Example: /load
         * 
         *  When using with parameters: /load/[i:id]
         * 
         *  Parameters: [type:name]
         *  For type use:
         *      - '*' -> any type
         *      - ''  -> alias for '*'
         *      - 's' -> string
         *      - 'i' -> integer
         *      - 'n' -> number
         */
        private $path = null;

        /**
         * @var callable The function that should be executed when this
         *  route is matched. Has to return value that should be displayed
         */
        private $target = null;

        /**
         * @var string The name of this route
         */
        private $name = null;

        /**
         * Creates Route
         * 
         * @param string $method
         * @param string $path
         * @param string $target
         * @param string $name
         */
        public function __construct($method, $path, $target, $name) {
            $this->setMethod($method);
            $this->setPath($path);
            $this->setTarget($target);
            $this->setName($name);
        }

        /**
         * Sets the method this route should match
         * @see $method
         * 
         * @param string $method
         * @throws Exception
         * @return void
         */
        public function setMethod($method) {
            if(empty($method)){
                $this->method = null;
                return;
            }

            if(!is_string($method)) {
                throw new Exception('Method must be string');
            }

            if(!in_array($method, PAF::getMethods())) {
                throw new Exception('Method not supported');
            }

            $this->method = strtoupper($method);
        }

        /**
         * Returns the method this route should match
         * 
         * @return string
         */
        public function getMethod() {
            return $this->method;
        }

        /**
         * Sets the path this route should match
         * @see $path
         * 
         * @param string $path
         * @throws Exception
         * @return void
         */
        public function setPath($path) {
            if(empty($path)){
                $this->path = null;
                return;
            }

            if(!is_string($path)) {
                throw new Exception('Path must be string');
            }

            $this->path = strtolower($path);
        }

        /**
         * Returns the path this route should match
         * 
         * @return string
         */
        public function getPath() {
            return $this->path;
        }

        /**
         * Sets the target this route should execute when matched
         * @see $target
         * 
         * @param callable $target
         * @throws Exception
         * @return void
         */
        public function setTarget($target) {
            if(empty($target)){
                $this->target = null;
                return;
            }

            if(!is_callable($target)) {
                throw new Exception('Target must be callable');
            }

            $this->target = $target;
        }

        /**
         * Returns the target this route should execute when matched
         * 
         * @return callable
         */
        public function getTarget() {
            return $this->target;
        }

        /**
         * Sets the name of this route
         * @see $name
         * 
         * @param string $name
         * @throws Exception
         * @return void
         */
        public function setName($name) {
            if(empty($name)){
                $this->name = null;
                return;
            }

            if(!is_string($name)) {
                throw new Exception('Name must be string');
            }

            $this->name = $name;
        }

        /**
         * Returns the name of this route
         * 
         * @return string
         */
        public function getName() {
            return $this->name;
        }

        /**
         * Checks if this route is valid:
         *  - method, path and target are not null
         *  - the method is supported (contained in $methods)
         * 
         * @param array $methods The supported methods (optional)
         * @throws Exception
         * @return bool True if valid otherwise false
         */
        public function verify($methods = null) {
            if(is_array($methods)){
                if(!in_array($this->method, $methods)){
                    throw new Exception('Method not allowed on this router');
                }
            }
            return !empty($this->method) && !empty($this->path) && !empty($this->target);
        }
    }

    /**
     * This class defines a Response
     * 
     * @license MIT
     * @author Matthias Thalmann
     */
    class Response {
        /**
         * @var mixed The value of the response that should be displayed 
         */
        public $value = null;

        /**
         * @var int The http-response-code of the response
         */
        public $code = 200;

        /**
         * Creates Response
         * 
         * @param mixed $value
         * @param int $code
         */
        public function __construct($value = null, $code = 200) {
            $this->value = $value;
            $this->code = $code;
        }

        /*
         * Helper functions to set http-response-code
         */
        public function ok(){ $this->code = 200; return $this; }
        public function created(){ $this->code = 201; return $this; }
        public function badRequest(){ $this->code = 400; return $this; }
        public function unauthorized(){ $this->code = 401; return $this; }
        public function forbidden(){ $this->code = 403; return $this; }
        public function notFound(){ $this->code = 404; return $this; }
        public function methodNotAllowed(){ $this->code = 405; return $this; }
        public function conflict(){ $this->code = 409; return $this; }
        public function error(){ $this->code = 500; return $this; }
        public function notImplemented(){ $this->code = 501; return $this; }
    }
?>