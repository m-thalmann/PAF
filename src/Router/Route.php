<?php

namespace PAF\Router;

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
     * @see Route::setPath()
     */
    private $path = null;

    /**
     * @var string Regex-string for filtering the parameters and matching the url/path
     * @see Route::setPath() This string is generated by the setPath function
     */
    private $pathRegex = null;

    /**
     * @var array The parameters of the path: ["name" => string, "type" => string][].
     *            The type is according to the setPath function
     * @see Route::setPath()
     */
    private $params = null;

    /**
     * @var callable[]  The functions that should be executed when this
     *                  route is matched. Has to return value that should be displayed.
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
     * @see Route::$method
     *
     * @param string $method
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setMethod($method) {
        if (empty($method)) {
            $this->method = null;
            return;
        }

        if (!is_string($method)) {
            throw new \InvalidArgumentException('Method must be string');
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
     *
     * Can also use parameters: /load/{{i:id}}, that are passed on to
     * the first target-function ($req['params'])
     *
     * Can contain regex, but when using groups, some things might not work correctly. Therefore please ommit the '(' and ')' characters.
     *
     * Parameters: {{type:name}}
     * For type use:
     *      - '*' -> any type
     *      - ''  -> alias for '*'
     *      - 's' -> string
     *      - 'i' -> integer
     *      - 'n' -> number
     *
     * @param string $path
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setPath($path) {
        if (empty($path)) {
            $path = '';
        }

        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be string');
        }

        if ($path === '/') {
            $path = '';
        }

        $this->params = [];
        $pathRegex = '';

        if (strlen($path) > 0) {
            $pathRegex = preg_replace_callback(
                '/\{\{(?: )?(|\*|s|i|n):(\w+)(?: )?\}\}/',
                function ($match) {
                    $type =
                        $match[1] !== '' && $match[1] !== 's' ? $match[1] : '*';
                    $this->params[] = [
                        "name" => $match[2],
                        "type" => $type,
                    ];

                    if ($type === '*') {
                        return '(.+)';
                    } elseif ($type === 'i') {
                        return '(-?\d+)';
                    } elseif ($type === 'n') {
                        return '(-?\d+\.?\d*)';
                    } else {
                        return '';
                    }
                },
                str_replace('/', '\\/', $path)
            );
        }

        $this->path = $path;
        $this->pathRegex = '/^' . $pathRegex . '$/';
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
     *
     * The first target function should look the following:
     *
     * <code>
     *  function($request, $next) // if no further target functions, $next is null
     * </code>
     *
     * $request contains the request data:
     *
     * <pre>
     *  [
     *      'route' => Route,       // matched route object
     *      'method' => string,     // http-request-method
     *      'url' => string,        // full request url
     *      'path' => string,       // matched path of the route
     *      'authorization' => string|null // contains the content of the authorization header if it is set
     *      'params' => array/map,  // map containing all parameters of the path (converted to datatype)
     *      'post' => mixed|null,   // posted data, if data was posted (not formdata -> use $_POST)
     *  ]
     * </pre>
     *
     * All further target functions must be called via the second parameter. Only the return value of the first
     * target function is output.
     *
     * <code>
     *  function($req, $next){
     *      return $next($data); # second parameter will be set to $next
     *  }
     * </code>
     *
     * @param array $targets
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setTargets($targets) {
        if (empty($targets)) {
            $this->targets = [];
            return;
        }

        if (!is_array($targets)) {
            if (is_callable($targets)) {
                $targets = [$targets];
            } else {
                throw new \InvalidArgumentException(
                    'Targets must be array of callables'
                );
            }
        }

        foreach ($targets as $target) {
            if (!is_callable($target)) {
                throw new \InvalidArgumentException('Targets must be callable');
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
     * Checks if this route matches with the provided method and path.
     *
     * @param string $method The method that should be matched
     * @param string $path The path that should be matched
     * @return bool True if the route matches with the path and method, false otherwise
     */
    public function matches($method, $path) {
        if ($this->getMethod() !== '*' && $this->getMethod() !== $method) {
            return false;
        }

        return preg_match($this->pathRegex, $path);
    }

    /**
     * Executes this route, by matching the path with the set path, thus getting the parameters
     * and calling the target functions with the request data and returning the overall return value
     *
     * @param string $method The method of the route
     * @param string $path The matched path
     * @param array $request The request data
     * @see Route::setTargets() for the request data
     *
     * @return mixed The overall return value of the target functions
     */
    public function execute($method, $path, $request) {
        $matchedParams = [];

        if (!preg_match($this->pathRegex, $path, $matchedParams)) {
            return null;
        }

        $params = [];

        for ($i = 1; $i < count($matchedParams); $i++) {
            if ($this->params[$i - 1]['type'] === 'i') {
                $params[$this->params[$i - 1]['name']] = intval(
                    $matchedParams[$i]
                );
            } elseif ($this->params[$i - 1]['type'] === 'n') {
                $params[$this->params[$i - 1]['name']] = doubleval(
                    $matchedParams[$i]
                );
            } else {
                $params[$this->params[$i - 1]['name']] = $matchedParams[$i];
            }
        }

        $request['route'] = $this;
        $request['params'] = $params;

        $targets = $this->getTargets();

        $next = null;

        if (count($targets) > 1) {
            $lastTarget = 0;

            $next = function ($data) use ($targets, &$lastTarget, &$next) {
                $_next = null;

                $lastTarget++;

                if (count($targets) - $lastTarget > 1) {
                    $_next = $next;
                }

                return $targets[$lastTarget]($data, $_next);
            };
        }

        return $targets[0]($request, $next);
    }
}
