<?php namespace Zephyrus\Application;

use Zephyrus\Exceptions\RouteDefinitionException;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;

abstract class RouterEngine
{
    /**
     * @var mixed[] Associative array that contains all defined routes
     */
    private $routes = [];

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
     * @var callable
     */
    private $before = null;

    /**
     * @var callable
     */
    private $after = null;

    /**
     * Launch the routing process to determine, according to the
     * initiated request, the best route to execute. Cannot be overridden.
     *
     * @param Request $request
     * @throws \Exception
     */
    final public function run(Request $request)
    {
        $this->request = $request;
        $this->requestedUri = $this->request->getPath();
        $this->requestedMethod = strtoupper($this->request->getMethod());
        $this->requestedRepresentation = $this->request->getAccept();
        $this->verifyRequestMethod();
        $route = $this->findRouteFromRequest();
        $this->prepareResponse($route);
    }

    /**
     * @return Request
     */
    final public function &getRequest()
    {
        return $this->request;
    }

    /**
     * Add a new route for the application. Make sure to create the
     * adequate structure with corresponding parameters regex pattern if
     * needed. Cannot be overridden.
     *
     * @param string $method
     * @param string $uri
     * @param callable $callback
     * @param string | array | null $acceptedFormats
     * @throws RouteDefinitionException
     */
    final protected function addRoute($method, $uri, $callback, $acceptedFormats)
    {
        if ($uri != '/') {
            $uri = rtrim($uri, '/');
        }
        $params = $this->getUriParameters($uri);
        if (!empty($params) && count($params) != count(array_unique($params))) {
            throw new RouteDefinitionException($uri);
        }

        $this->routes[$method][] = [
            'uri' => $uri,
            'regex' => (!empty($params))
                ? $this->getUriRegexFromParameters($uri, $params)
                : null,
            'params' => $params,
            'callback' => $callback,
            'acceptedRequestFormats' => $acceptedFormats
        ];
    }

    /**
     * Method called immediately before calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made prior the route callback.
     *
     * @param mixed[] $route
     */
    protected function beforeCallback($route)
    {
    }

    /**
     * Method called immediately after calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made right after the route callback.
     *
     * @param mixed[] $route
     */
    protected function afterCallback($route)
    {
    }

    /**
     * @param $before
     */
    public function setBeforeCallback($before)
    {
        $this->before = $before;
    }

    /**
     * @param $after
     */
    public function setAfterCallback($after)
    {
        $this->after = $after;
    }

    /**
     * Find a route corresponding to the client request. Matches direct
     * URIs and parametrised URIs. When a match is found, there's a last
     * verification to check if the route is accepted (through method
     * isRequestAcceptedForRoute). The first route to properly match the
     * request is then return. Hence the need to declare routes in
     * correct order.
     *
     * @throws \Exception
     * @return mixed[]
     */
    private function findRouteFromRequest()
    {
        $routes = $this->routes[$this->requestedMethod];
        foreach ($routes as $route) {
            $pattern = '/^' . $route['regex'] . '$/';
            $match = !empty($route['regex']) && preg_match($pattern, $this->requestedUri);
            if ($route['uri'] == $this->requestedUri || $match) {
                if (!$this->isRequestAcceptedForRoute($route)) {
                    throw new RouteNotAcceptedException($this->requestedRepresentation);
                }
                return $route;
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
        if (is_null($acceptedFormats)) {
            return true;
        }
        if (is_array($acceptedFormats)) {
            foreach ($acceptedFormats as $format) {
                if (strpos($this->requestedRepresentation, $format) !== false) {
                    return true;
                }
            }
            return false;
        }
        return strpos($this->requestedRepresentation, $acceptedFormats) !== false;
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
        $values = $this->retrieveUriParameters($route);
        $this->loadRequestParameters($route, $values);
        $this->beforeCallback($route);
        $responseBefore = $this->executeBefore();
        if ($responseBefore instanceof Response) {
            $response = $responseBefore;
        } else {
            $callback = new Callback($route['callback']);
            $arguments = $this->getFunctionArguments($callback->getReflection(), $values);
            $response = $callback->executeArray($arguments);
        }
        $responseAfter = $this->executeAfter($response);
        if ($responseAfter instanceof Response) {
            $response = $responseAfter;
        }
        if ($response instanceof Response) {
            $response->send();
        }
        $this->afterCallback($route);
    }

    /**
     * Executes the user specified before callback.
     *
     * @return mixed | null
     */
    private function executeBefore()
    {
        if (!is_null($this->before)) {
            $c = new Callback($this->before);
            return $c->execute();
        }
        return null;
    }

    /**
     * Executes the user specified after callback which receives the previous
     * obtained response wither from the before callback or natural execution.
     *
     * @param null | Response $response
     * @return mixed | null
     */
    private function executeAfter(?Response $response)
    {
        if (!is_null($this->after)) {
            $c = new Callback($this->after);
            return $c->execute($response);
        }
        return null;
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param \ReflectionFunctionAbstract $reflection
     * @return array
     */
    private function getFunctionArguments(\ReflectionFunctionAbstract $reflection, $values)
    {
        $arguments = [];
        if (!empty($reflection->getParameters())) {
            foreach ($values as $value) {
                $arguments[] = $value;
            }
        }
        return $arguments;
    }

    /**
     * Load parameters located inside the request object.
     *
     * @param mixed[] $route
     * @param mixed[] $values
     * @throws \Exception
     */
    private function loadRequestParameters($route, $values)
    {
        if (!empty($route['regex'])) {
            $i = 0;
            foreach ($route['params'] as $param) {
                $this->request->prependParameter($param, $values[$i]);
                ++$i;
            }
        }
    }

    private function retrieveUriParameters($route)
    {
        $values = [];
        if (!empty($route['regex'])) {
            $pattern = '/^' . $route['regex'] . '$/';
            preg_match_all($pattern, $this->requestedUri, $matches);
            $matchCount = count($matches);
            for ($i = 1; $i < $matchCount; ++$i) {
                $values[] = /*purify(*/$matches[$i][0]/*)*/;
            }
        }
        return $values;
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
