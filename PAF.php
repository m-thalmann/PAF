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
         * @var bool Boolean whitch defines if CORS should be enabled for this instance
         */
        private $cors_enabled = false;

        /**
         * @var array Array of all routes for this instance
         */
        private $routes = array();

        /**
         * @var string String of the path, from where on the route will be matched
         *  for example: https://example.com/api/load and basePath is set to /api then it will
         *  match only /load
         */
        private $basePath = '';

        private $headers = [];

        /**
         * Creates PAF-Router
         * 
         * @param string $basePath
         */
        public function __construct($basePath = '', $headers = []) {
            $this->setBasePath($basePath);
            $this->setHeaders($headers);
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
         * Sets the base path for this instance
         * 
         * @param string $path
         * @return void
         */
        public function setBasePath($path = '') {
            if(is_string($path)){
                $this->basePath = $path;
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
         * Sets custom headers
         * 
         * @param array $headers
         *  [
         *      "name" => "value",
         *      ...
         *  ]
         * @return void
         */
        public function setHeaders($headers){
            if(!is_array($headers)){
                throw new Exception('Headers must be array');
            }

            foreach($headers as $key => $header){
                $this->setHeader($key, $header);
            }
        }

        /**
         * Sets a custom header
         * 
         * @param string $name
         * @param string $value
         * @return void
         */
        public function setHeader($name, $value){
            if(is_string($name) && $value == null){
                unset($this->headers[$name]);
            }
            if(!is_string($name) || !is_string($value)){
                throw new Exception('Name and value must be string');
            }

            $this->headers[$name] = $value;
        }

        /**
         * Returns the custom headers for this router
         * 
         * @return array
         */
        public function getHeaders(){
            return $this->headers;
        }

        /**
         * Returns the custom header for this router
         * 
         * @param string $name
         * @return string
         */
        public function getHeader($name){
            if(!is_string($name)){
                throw new Exception('Name must be string');
            }

            $ret = null;

            if(isset($this->headers[$name])){
                $ret = $this->headers[$name];
            }

            return $ret;
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
         * Adds a new Route to the routes-array
         * 
         * @param string $method
         * @param string $route
         * @param callables $target1, $target2... (in $targets)
         * 
         * @throws Exception
         * @return void
         */
        public function map($method, $route) {
            $targets = [];
            $num_args = func_num_args();

            if($num_args > 2){
                for($i = 2; $i < $num_args; $i++){
                    $targets[] = func_get_arg($i);
                }
            }

            $this->addRoute(new Route($method, $route, $targets));
        }

        /**
         * Adds a new Route to the routes-array
         * 
         * @param Route $route
         * @throws Exception
         * @return void
         */
        public function addRoute($route){
            if($route instanceof Route && $route->verify()){
                $this->routes[] = $route;
            }else{
                throw new Exception('Route must be Route and be valid');
            }
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
                    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
                    'path' => $requestUrl,
                    'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null,
                    'params' => [],
                    'post' => null,
                ];

                if($requestMethod == 'POST' || $requestMethod == 'PUT'){
                    $request['post'] = json_decode(file_get_contents('php://input'), true);
                }

                foreach($this->routes as $route){
                    $method = $route->getMethod();
                    $path = $route->getPath();

                    $request['route'] = $route;

                    if($method == '*' || $method == $requestMethod){
                        if($path == '*'){
                            $this->resolve($request);
                            return true;
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
                                    $this->resolve($request);
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
         * Is a alias for execute()
         * 
         * @see execute
         * @return bool True if a match was found otherwise false
         */
        public function exec(){
            return $this->execute();
        }

        /**
         * Executes the first target function and generates the $next-functions.
         * The output is then given to the output method
         * 
         * @param mixed $request The response-array, that has matched
         * @return void
         */
        private function resolve($request){
            $targets = $request['route']->getTargets();

            $next = null;
            
            if(count($targets) > 1){
                $last_target = 0;

                $next = function($data) use ($targets, $last_target, $next){
                    $_next = null;

                    $last_target++;

                    if(count($targets) - $last_target > 1){
                        $_next = function($data) use ($next){
                            $next($data);
                        };
                    }

                    return $targets[$last_target]($data, $_next);
                };
            }

            $ret = $targets[0]($request, $next);
            
            $this->output($ret);
        }

        /**
         * Sets the http-headers and outputs the value of $ret.
         * If $ret is a object, it is tried to execute the toJSON-function
         * 
         * @param mixed $ret The value to output
         */
        public function output($ret){
            header('Content-Type: application/json');

            $allowedMethods = [];

            foreach($this->routes as $route){
                $method = $route->getMethod();
                if(!in_array($method, $allowedMethods)){
                    $allowedMethods[] = $route->getMethod();
                }
            }

            header("Access-Control-Allow-Methods: " . implode(', ', $allowedMethods));

            if($this->cors_enabled){
                header("Access-Control-Allow-Origin: *");
            }

            foreach($this->headers as $key => $header){
                header("$key: $header");
            }

            $value = null;
            $code = 200;

            if($ret instanceof Response){
                if(!$ret->verify()){
                    throw new Exception('Response object is not correct');
                }

                $value = $ret->value;
                $code = $ret->code;
            }else{
                $value = $ret;
            }

            $value = PAF::convertResponse($value);

            http_response_code($code);

            echo json_encode($value);
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
         * Converts the return value (response), by calling, if possible, toJSON on the
         * object(s)
         */
        private static function convertResponse($value){
            if(is_object($value)){
                if(method_exists($value, 'toJSON')){
                    $value = PAF::convertResponse($value->toJSON());
                }
            }else if(is_array($value)){
                $ret = [];
                foreach($value as $key => $val){
                    $ret[$key] = PAF::convertResponse($val);
                }

                $value = $ret;
            }

            return $value;
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
         * @var array The functions that should be executed when this
         *  route is matched. Has to return value that should be displayed
         * 
         *  function($request, $next){ ... }
         * 
         *  $next is the function that is called to execute the next target function
         */
        private $targets = [];

        /**
         * Creates Route
         * 
         * @param string $method
         * @param string $path
         * @param array $targets
         */
        public function __construct($method, $path, $targets) {
            $this->setMethod($method);
            $this->setPath($path);
            $this->setTargets($targets);
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

            $this->path = $path;
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
         * Sets the targets this route should execute when matched
         * @see $targets
         * 
         * @param array $targets
         * @throws Exception
         * @return void
         */
        public function setTargets($targets) {
            if(empty($targets)){
                $this->targets = [];
                return;
            }

            if(!is_array($targets)){
                if(is_callable($targets)){
                    $targets = [$targets];
                }else{
                    throw new Exception('Targets must be array of callables');
                }
            }

            foreach($targets as $target){
                if(!is_callable($target)) {
                    throw new Exception('Targets must be callable');
                }
            }

            $this->targets = $targets;
        }

        /**
         * Returns the targets this route should execute when matched
         * 
         * @return array
         */
        public function getTargets() {
            return $this->targets;
        }

        /**
         * Checks if this route is valid:
         *  - method, path and target are not null
         * 
         * @throws Exception
         * @return bool True if valid otherwise false
         */
        public function verify() {
            return !empty($this->method) && !empty($this->path) && !empty($this->targets);
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

        /**
         * Verifies if this object is correct
         * 
         * @return bool true if correct, otherwise false
         */
        public function verify(){
            return !empty($this->code) && is_int($this->code);
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
