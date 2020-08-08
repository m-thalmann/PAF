<?php
    /**
     * This class defines a router
     * 
     * @license MIT
     * @author Matthias Thalmann
     */
    class Router{
        /**
         * @var string  The base path, from where the router should match.
         *              Example: basePath = '/api' so the route '/test' is matched with the following url: '/api/test'
         */
        private static $basePath = '';

        /**
         * @var array The custom http-headers that are set on a response
         * @see Router::output()
         */
        private static $headers = [];

        /**
         * @var bool Sets if the corresponding headers, to prevent cors, are set
         */
        private static $corsEnabled = FALSE;

        /**
         * @var bool    Sets if the query string is ignored when matching the url.
         *              Example: '/api/test?api_token=123' with ignoreQuery = TRUE only checks /api/test for a matching route
         */
        private static $ignoreQuery = TRUE;

        /**
         * @var RouterGroup The root-route of the router (grouped by the base-path)
         */
        private static $group = NULL;

        /**
         * @var bool Sets if the router has been initialized
         */
        private static $inited = FALSE;

        /**
         * Router should not be instantiatable
         */
        private function __construct() { }

        /**
         * Initializes the router by creating the root-group and setting if cors should be enabled.
         * 
         * A initialization is necessary and can only happen once.
         * 
         * @param string $basePath The base path, from where the router should match
         * @param bool $corsEnabled If cors should be enabled
         */
        public static function init($basePath = '', $corsEnabled = FALSE){
            // can only be inited once
            if(self::$inited){
                throw new Exception('Router already initialized');
            }

            if($basePath === '/') $basePath = '';

            self::setBasePath($basePath);
            self::setCorsEnabled($corsEnabled);

            self::$group = new RouterGroup($basePath);

            self::$inited = TRUE;
        }

        /**
         * Sets the base path for the router
         * 
         * @param string $path The path to be set
         */
        private static function setBasePath($path) {
            if(is_string($path)){
                self::$basePath = $path;
            }else{
                throw new InvalidArgumentException('Path must be string');
            }
        }

        /**
         * Returns the set base path
         * 
         * @return string The set base path
         */
        public static function getBasePath() {
            return self::$basePath;
        }

        /**
         * Adds/replaces all headers from the parameter (map) to the custom headers to be set
         * 
         * To delete a header set the value to NULL
         * 
         * @param array $headers The headers to be set, with the header-name as key and the value as value
         */
        public static function setHeaders($headers){
            if(!is_array($headers)){
                throw new InvalidArgumentException('Headers must be array');
            }

            foreach($headers as $key => $header){
                self::setHeader($key, $header);
            }
        }

        /**
         * Adds/replaces the header to the custom headers with the value
         * 
         * To delete a header set the value to NULL
         * 
         * @param string $name The name of the header
         * @param string $value The value of the header
         */
        public static function setHeader($name, $value){
            if(is_string($name) && $value === NULL){
                unset(self::$headers[$name]);
            }
            if(!is_string($name) || !is_string($value)){
                throw new InvalidArgumentException('Name and value must be string');
            }

            self::$headers[$name] = $value;
        }

        /**
         * Returns all set custom headers
         * 
         * @return array The set custom http-headers
         */
        public static function getHeaders(){
            return self::$headers;
        }

        /**
         * Returns a specific custom http-header
         * 
         * @param string $name The name of the header
         * @return string The value of the set header
         */
        public static function getHeader($name){
            if(!is_string($name)){
                throw new InvalidArgumentException('Name must be string');
            }

            $ret = NULL;

            if(isset(self::$headers[$name])){
                $ret = self::$headers[$name];
            }

            return $ret;
        }

        /**
         * Sets whether cors should be enabled
         * 
         * <b>Tipp:</b>
         * If you want cors to work and you pass a authorization-header or a content-type header,
         * you have to add a 'Access-Control-Allow-Headers' header with the wanted headers as value
         * (separated by ',')
         * 
         * @param bool $enabled If cors should be enabled or not
         * @see Router::$corsEnabled
         */
        public static function setCorsEnabled($enabled){
            self::$corsEnabled = !!$enabled;
        }

        /**
         * Returns whether cors is enabled
         * 
         * @return bool If cors is enabled or not
         */
        public static function getCorsEnabled(){
            return self::$corsEnabled;
        }

        /**
         * Sets whether the query-part of the url should be ignored or not
         * 
         * @param bool $ignored If the query-part should be ignored
         * @see Router::$ignoreQuery
         */
        public static function setIgnoreQuery($ignored){
            self::$ignoreQuery = !!$ignored;
        }

        /**
         * Returns whether the query-part of the url should be ignored or not
         * 
         * @return bool If the query-part is ignored or not
         */
        public static function getIgnoreQuery(){
            return self::$ignoreQuery;
        }

        /**
         * Get the default group, to add routes directly to the base path
         * 
         * @return RouterGroup The default group (with the base path)
         */
        public static function addRoutes(){
            return self::group(NULL);
        }

        /**
         * Adds (or returns if existing) a group to the default group and returns the object
         * If the $lazyPath parameter is set, it is set (if the group not yet exists within the default group).
         * For explanations on the lazy-path see: RouterGroup::$lazyPath
         * 
         * @param string $group The group path
         * @param string $lazyPath The optional path to a file, that is only loaded, when the group is matched
         * @see RouterGroup::$lazyPath
         * @param string $lazyVariable The name of the variable, where the group can be accessed in the $lazyPath
         * 
         * @return RouterGroup The existing/created router group for adding routes etc.
         */
        public static function group($group, $lazyPath = NULL, $lazyVariable = 'group'){
            if(!self::$inited){
                throw new Exception('Router not initialized');
            }

            if($group === NULL){
                return self::$group;
            }

            return self::$group->group($group, $lazyPath, $lazyVariable);
        }

        /**
         * Executes the router, meaning it is matching the request url and method with
         * the corresponding route, by calling the resolve function of the default group recusively.
         * If a route is found, the output function is called with the result of the execute-function of
         * the route.
         * 
         * @see Router::output()
         * 
         * @return bool If the route could be matched or not; if there was no route found, there will not happen any output
         */
        public static function execute(){
            if(!self::$inited){
                throw new Exception('Router not initialized');
            }

            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

            if(substr($requestUrl, strlen($requestUrl) -1) == '/'){
                $requestUrl = substr($requestUrl, 0, strlen($requestUrl) -2);
            }

            if(self::getIgnoreQuery()){
                $requestUrl = explode('?', $requestUrl)[0];
            }

            if(substr($requestUrl, 0, strlen(self::$basePath)) === self::$basePath){
                if(self::$corsEnabled){
                    if($requestMethod == 'OPTIONS'){
                        self::output(NULL);
                        return TRUE;
                    }
                }

                $requestUrl = substr($requestUrl, strlen(self::$basePath));
                $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

                $route = self::$group->resolve($requestMethod, $requestUrl);

                $postData = NULL;

                if($requestMethod == 'POST' || $requestMethod == 'PUT'){
                    $postData = json_decode(file_get_contents('php://input'), TRUE);
                }

                if($route !== NULL){
                    $outputValue = $route['route']->execute($route['method'], $route['path'], [
                        'route' => NULL,
                        'method' => $requestMethod,
                        'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
                        'path' => $requestUrl,
                        'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : NULL,
                        'params' => [],
                        'post' => $postData,
                    ]);

                    self::output($outputValue);

                    return TRUE;
                }
            }

            return FALSE;
        }

        /**
         * Sets the headers (with cors-headers, if cors is enabled, and content-type),
         * tries to convert the value of the parameter to json (only if the content-type is json),
         * sets the http-response-code and outputs the (converted) parameter value
         * 
         * @param mixed $ret The output value
         */
        public static function output($ret){
            $contentType = 'application/json';

            if($ret instanceof Response){
                $contentType = $ret->contentType;
            }

            header('Content-Type: ' . $contentType);

            if(self::$corsEnabled && !isset(self::$headers['Access-Control-Allow-Origin'])){
                header("Access-Control-Allow-Origin: *");
            }

            if(self::$corsEnabled && !isset(self::$headers['Access-Control-Allow-Methods'])){
                header("Access-Control-Allow-Methods: *");
            }

            foreach(self::$headers as $key => $header){
                header("$key: $header");
            }

            $value = NULL;
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

            http_response_code($code);

            if($contentType == 'application/json'){
                $value = self::convertResponse($value);
                echo json_encode($value);
            }else{
                echo $value;
            }
        }

        /**
         * Converts a value to json, if possible.
         * This happenes by calling the toJSON() function of the object (if it is an object
         * and it has that function). If the value is a array the function is called for
         * every element recursively.
         * 
         * @param mixed $value The value that should be converted
         * @return mixed The converted value response
         */
        private static function convertResponse($value){
            if(is_object($value)){
                if(method_exists($value, 'toJSON')){
                    $value = self::convertResponse($value->toJSON());
                }
            }else if(is_array($value)){
                $ret = [];
                foreach($value as $key => $val){
                    $ret[$key] = self::convertResponse($val);
                }

                $value = $ret;
            }

            return $value;
        }
    }
?>