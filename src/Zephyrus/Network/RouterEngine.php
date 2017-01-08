<?php namespace Zephyrus\Network;

use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\RouteDefinitionException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;

abstract class RouterEngine
{
    /**
     * @var mixed[] Associative array that contains all defined routes
     */
    private $routes = [];

    /**
     * @var bool Route acceptance verification mode
     */
    private $strict = false;

    /**
     * @var string HTTP method associated with current request
     */
    private $requestedMethod;

    /**
     * @var string Complete uri request
     */
    private $requestedUri;

    /**
     * @var string HTTP accept directive specified by the client
     */
    private $requestedRepresentation;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var int Route processing start time to get total execution time
     */
    private $startTime = -1;

    /**
     * Keeps references of request uri and request method
     */
    public function __construct()
    {
        $this->request = RequestFactory::create();
        $this->requestedUri = $this->request->getPath();
        $this->requestedMethod = $this->request->getMethod();
        $this->requestedRepresentation = $this->request->getAccept();
    }

    /**
     * Launch the routing process to determine, according to the
     * initiated request, the best route to execute. Cannot be overridden.
     */
    public final function run()
    {
        $this->startTime = microtime(true);
        $this->verifyRequestMethod();
        $route = $this->findRouteFromRequest();
        $this->prepareResponse($route);
    }

    /**
     * Get if the route acceptance verification mode must be strict. In
     * strict mode the value of the HTTP_ACCEPT header must exactly be
     * the same as the one specified in the route. Cannot be overridden.
     *
     * @return bool
     */
    public final function isStrict()
    {
        return (bool)$this->strict;
    }

    /**
     * Set if the route acceptance verification mode must be strict. In
     * strict mode the value of the HTTP_ACCEPT header must exactly be
     * the same as the one specified in the route. Cannot be overridden.
     *
     * @param bool $strict
     */
    public final function setStrict($strict)
    {
        $this->strict = (bool)$strict;
    }

    /**
     * @return string
     */
    public final function getRequestedMethod()
    {
        return $this->requestedMethod;
    }

    /**
     * @return string
     */
    public final function getRequestedUri()
    {
        return $this->requestedUri;
    }

    /**
     * @return int
     */
    public final function getElapsedSeconds()
    {
        return ($this->startTime == -1)
            ? 0
            : microtime(true) - $this->startTime;
    }

    /**
     * Add a new route for the application. Make sure to create the
     * adequate structure with corresponding parameters regex pattern if
     * needed. Extensible because of the <extras> arguments which can be
     * provided optionally by children implementations. Cannot be
     * overridden.
     *
     * @param string $method
     * @param string $uri
     * @param callable $callback
     * @param string | array | null $acceptedRequestFormats
     * @param mixed[] $extras (optional)
     * @throws RouteDefinitionException
     * @throws RouteMethodUnsupportedException
     */
    protected final function addRoute($method, $uri,  $callback,
                                      $acceptedRequestFormats,
                                      $extras = [])
    {
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new RouteMethodUnsupportedException($method);
        }
        if ($uri != '/') {
            $uri = rtrim($uri, '/');
        }

        $params = $this->getUriParameters($uri);
        if (!empty($params) && count($params) != count(array_unique($params))) {
            throw new RouteDefinitionException($uri);
        }

        $this->routes[$method][] = [
            'uri'                    => $uri,
            'regex'                  => (!empty($params))
                ? $this->getUriRegexFromParameters($uri, $params)
                : null,
            'params'                 => $params,
            'callback'               => $callback,
            'acceptedRequestFormats' => $acceptedRequestFormats,
            'extras'                 => $extras
        ];
    }

    /**
     * Method called immediately before calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made prior the route callback.
     *
     * @param mixed[] $route
     */
    protected function beforeCallback($route) { }

    /**
     * Method called immediately after calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made right after the route callback.
     *
     * @param mixed[] $route
     */
    protected function afterCallback($route) { }

    /**
     * Find a route corresponding to the client request. Matches direct
     * URIs and parametrised URIs. When a match is found, there's a last
     * verification to check if the route is accepted (through method
     * isRequestAcceptedForRoute). The first route to properly match the
     * request is then return. Hence the need to declare routes in
     * correct order.
     *
     * @return mixed[]
     * @throws \Exception
     */
    private function findRouteFromRequest()
    {
        $routes = $this->routes[$this->requestedMethod];
        foreach ($routes as $route) {
            $pattern = '/^' . $route['regex'] . '$/';
            if ($route['uri'] == $this->requestedUri || (!empty($route['regex']) && preg_match($pattern, $this->requestedUri))) {
                if ($this->isRequestAcceptedForRoute($route)) {
                    return $route;
                } else {
                    throw new RouteNotAcceptedException($this->requestedRepresentation);
                }
            }
        }
        throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
    }

    /**
     * Determines if the request is acceptable for the specified route. Will
     * check for accepted request formats in both modes (strict and
     * normal).
     *
     * @param mixed[] $route
     * @return bool
     */
    private function isRequestAcceptedForRoute($route)
    {
        $acceptedFormats = $route['acceptedRequestFormats'];
        if (!is_null($acceptedFormats)) {
            if (is_array($acceptedFormats)) {
                $matches = 0;
                foreach ($acceptedFormats as $format) {
                    if (strpos($this->requestedRepresentation, $format) !== false) {
                        ++$matches;
                    }
                }

                return ($this->strict && $matches == count($acceptedFormats))
                    || (!$this->strict && $matches >= 1);
            } else {
                return ($this->strict && $this->requestedRepresentation == $acceptedFormats)
                    || (!$this->strict && strpos($this->requestedRepresentation, $acceptedFormats) !== false);
            }
        }
        return true;
    }

    /**
     * Prepare the response which will be sent to the client once the
     * specified callback function for the route has been executed. Make
     * sure to load the GET parameters which could be used inside the
     * callback function (through method loadMissingRequestParameters).
     *
     * @param mixed[] $route
     * @throws \Exception
     */
    private function prepareResponse($route)
    {
        $this->loadRequestParameters($route);
        $this->beforeCallback($route);
        $this->executeCallback($route['callback']);
        $this->afterCallback($route);
    }

    /**
     * Execute the specified callback function or method.
     *
     * @param $callback
     * @throws \Exception
     */
    private function executeCallback($callback)
    {
        $isObjectMethod = is_array($callback);
        if ($isObjectMethod) {
            $this->executeMethod($callback);
        } else {
            $this->executeFunction($callback);
        }
    }

    /**
     * Execute the specified callback function
     *
     * @param $callback
     */
    private function executeFunction($callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $arguments = $this->getFunctionArguments($reflection);
        $reflection->invokeArgs($arguments);
    }

    /**
     * Execute the specified callback object method. Works with static calls
     * or instance method.
     *
     * @param $callback
     */
    private function executeMethod($callback)
    {
        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        $arguments = $this->getFunctionArguments($reflection);

        if ($reflection->isStatic()) {
            $reflection->invokeArgs(null, $arguments);
        } elseif (is_object($callback[0])) {
            $reflection->invokeArgs($callback[0], $arguments);
        } else {
            $instance = new $callback[0]();
            $reflection->invokeArgs($instance, $arguments);
        }
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param \ReflectionFunctionAbstract $reflection
     * @return array
     */
    private function getFunctionArguments(\ReflectionFunctionAbstract $reflection)
    {
        $arguments = [];
        if (!empty($reflection->getParameters())) {
            $requestedParameters = $this->request->getParameters();
            foreach ($requestedParameters as $name => $value) {
                $arguments[] = $value;
            }
        }
        return $arguments;
    }

    /**
     * Load parameters located inside the request object.
     *
     * @param mixed[] $route
     * @throws \Exception
     */
    private function loadRequestParameters($route)
    {
        if (!empty($route['regex'])) {
            $pattern = '/^' . $route['regex'] . '$/';
            preg_match_all($pattern, $this->requestedUri, $matches);

            $values = [];
            $n = count($matches);
            for ($i = 1; $i < $n; ++$i) {
                $values[] = /*purify(*/$matches[$i][0]/*)*/;
            }

            if (count($route['params']) != count($values)) {
                throw new \RuntimeException("Cannot properly load request GET parameters. Unexpected error.");
            }

            $i = 0;
            foreach ($route['params'] as $param) {
                $_GET[$param] = $values[$i];
                $this->request->prependParameter($param, $values[$i]);
                ++$i;
            }
        }
    }

    /**
     * Verify if the HTTP method used in the request is valid (GET,
     * POST, DELETE, PUT) and check if the method has at least one route
     * specified (through the veryMethodDefinition method). An exception
     * is thrown if one of these conditions are not satisfied.
     *
     * @throws \Exception
     */
    private function verifyRequestMethod()
    {
        if (!in_array($this->requestedMethod, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new RouteMethodUnsupportedException($this->requestedMethod);
        }
        if (!$this->isRequestedMethodHasDefinitions()) {
            throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
        }
    }

    /**
     * Verify if the method has at least one route specified. An
     * exception is thrown otherwise.
     *
     * @throws \Exception
     */
    private function isRequestedMethodHasDefinitions()
    {
        return array_key_exists($this->requestedMethod, $this->routes);
    }

    /**
     * Retrieve all parameters from the specified $uri. A valid parameter
     * is defined inside braces (e.g. {id}). Keeps the parameters ordinal
     * order. Accept every characters for parameter except "/".
     *
     * @param string $uri
     * @return array
     */
    private function getUriParameters($uri)
    {
        $params = [];
        $pattern = '/\{([^\/]+)\}/';
        if (preg_match_all($pattern, $uri, $results) > 0) {
            foreach ($results[1] as $result) {
                $params[] = $result;
            }
        }
        return $params;
    }

    /**
     * Retrieve a regex pattern matching each parameter specified in
     * $params inside the provided $uri. A valid parameter is defined
     * inside braces (e.g. {id}).
     *
     * @param string $uri
     * @param array $params
     * @return string
     */
    private function getUriRegexFromParameters($uri, $params)
    {
        $regex = str_replace('/', '\/', $uri);
        foreach ($params as $param) {
            $regex = str_replace('{' . $param . '}', '([^\/]+)', $regex);
        }
        return $regex;
    }
}