<?php
/**
 * iRouter.
 *
 * @version 1.0.0
 *
 * @see https://penobit.com
 *
 * @author Penobit <info@penobit.com>
 */

class iRouter {
    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected $routes = [];

    /**
     * @var array array of all named routes
     */
    protected $namedRoutes = [];

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $basePath = '';

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $matchTypes = [
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        'h' => '[0-9A-Fa-f]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/\.]++',
    ];

    /**
     * Create router in one call from config.
     *
     * @param string $basePath
     *
     * @throws Exception
     */
    public function __construct(array $routes = [], $basePath = '', array $matchTypes = []) {
        $this->addRoutes($routes);
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     *
     * @return array all routes
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Add multiple routes at once from array in the following format:.
     *
     *   $routes = [
     *      [$method, $route, $target, $name]
     *   ];
     *
     * @param array $routes
     *
     * @throws Exception
     *
     * @return void
     *
     * @author Koen Punt
     */
    public function addRoutes($routes) {
        if (!is_array($routes) && !$routes instanceof Traversable) {
            throw new RuntimeException('Routes should be an array or an instance of Traversable');
        }
        foreach ($routes as $route) {
            call_user_func_array([$this, 'map'], $route);
        }
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     *
     * @param string $basePath
     */
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes the key is the name and the value is the regex
     */
    public function addMatchTypes(array $matchTypes) {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target.
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route  The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed  $target The target where this route should point to. Can be anything.
     * @param string $name   Optional name of this route. Supply if you want to reverse route this url in your application.
     *
     * @throws Exception
     */
    public function map($method, $route, $target, $name = null) {
        $this->routes[] = [$method, $route, $target, $name];

        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new RuntimeException("Can not redeclare route '{$name}'");
            }
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Reversed routing.
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName the name of the route
     * @param array @params Associative array of parameters to replace placeholders with
     *
     * @throws Exception
     *
     * @return string the URL of the route with named parameters in place
     */
    public function generate($routeName, array $params = []) {
        // Check if named route exists
        if (!isset($this->namedRoutes[$routeName])) {
            throw new RuntimeException("Route '{$routeName}' does not exist.");
        }

        // Replace named parameters
        $route = $this->namedRoutes[$routeName];

        // prepend base path to route url again
        $url = $this->basePath.$route;

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                [$block, $pre, $type, $param, $optional] = $match;

                if ($pre) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    // Part is found, replace for param value
                    $url = str_replace($block, $params[$param], $url);
                } elseif ($optional && 0 !== $index) {
                    // Only strip preceding slash if it's not at the base
                    $url = str_replace($pre.$block, '', $url);
                } else {
                    // Strip match block
                    $url = str_replace($block, '', $url);
                }
            }
        }

        return $url;
    }

    /**
     * Match a given Request Url against stored routes.
     *
     * @param string $requestUrl
     * @param string $requestMethod
     *
     * @return array|bool array with route information on success, false on failure (no match)
     */
    public function match($requestUrl = null, $requestMethod = null) {
        $params = [];

        // set Request Url if it isn't passed as parameter
        if (null === $requestUrl) {
            $requestUrl = $_SERVER['REQUEST_URI'] ?? '/';
        }

        // strip base path from request url
        $requestUrl = substr($requestUrl, strlen($this->basePath));

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        $lastRequestUrlChar = $requestUrl ? $requestUrl[strlen($requestUrl) - 1] : '';

        // set Request Method if it isn't passed as a parameter
        if (null === $requestMethod) {
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        foreach ($this->routes as $handler) {
            [$methods, $route, $target, $name] = $handler;

            $method_match = (false !== stripos($methods, $requestMethod));

            // Method did not match, continue to next route.
            if (!$method_match) {
                continue;
            }

            if ('*' === $route) {
                // * wildcard (matches all)
                $match = true;
            } elseif (isset($route[0]) && '@' === $route[0]) {
                // @ regex delimiter
                $pattern = '`'.substr($route, 1).'`u';
                $match = 1 === preg_match($pattern, $requestUrl, $params);
            } elseif (($position = strpos($route, '[')) === false) {
                // No params in url, do string comparison
                $match = 0 === strcmp($requestUrl, $route);
            } else {
                // Compare longest non-param string with url before moving on to regex
                // Check if last character before param is a slash, because it could be optional if param is optional too (see https://github.com/penobit/iRouter/issues/241)
                if (0 !== strncmp($requestUrl, $route, $position) && ('/' === $lastRequestUrlChar || '/' !== $route[$position - 1])) {
                    continue;
                }

                $regex = $this->compileRoute($route);
                $match = 1 === preg_match($regex, $requestUrl, $params);
            }

            if ($match) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }

                return [
                    'target' => $target,
                    'params' => $params,
                    'name' => $name,
                ];
            }
        }

        return false;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE).
     *
     * @param $route
     *
     * @return string
     */
    protected function compileRoute($route) {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                [$block, $pre, $type, $param, $optional] = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ('.' === $pre) {
                    $pre = '\.';
                }

                $optional = '' !== $optional ? '?' : null;

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                        .('' !== $pre ? $pre : null)
                        .'('
                        .('' !== $param ? "?P<$param>" : null)
                        .$type
                        .')'
                        .$optional
                        .')'
                        .$optional;

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }
}
