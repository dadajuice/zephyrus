<?php namespace Zephyrus\Application;

use ReflectionFunctionAbstract;
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
     * @var array HTTP accept directive specified by the client
     */
    private $requestedRepresentations;

    /**
     * @var Request
     */
    private $request;

    /**
     * Launch the routing process to determine, according to the
     * initiated request, the best route to execute. Cannot be overridden.
     *
     * @param Request $request
     * @throws RouteNotFoundException
     * @throws RouteMethodUnsupportedException
     * @throws RouteNotAcceptedException
     */
    final public function run(Request $request)
    {
        $this->request = $request;
        $path = $this->request->getUri()->getPath();
        $this->requestedUri = ($path != "/") ? rtrim($path, "/") : "/";
        $this->requestedMethod = strtoupper($this->request->getMethod());
        $this->requestedRepresentations = $this->request->getAcceptedRepresentations();
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
     */
    final protected function addRoute($method, $uri, $callback, $acceptedFormats)
    {
        $this->routes[$method][] = [
            'route' => new Route($uri),
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
     * Find a route corresponding to the client request. Matches direct
     * URIs and parametrised URIs. When a match is found, there's a last
     * verification to check if the route is accepted (through method
     * isRequestAcceptedForRoute). The first route to properly match the
     * request is then return. Hence the need to declare routes in
     * correct order.
     *
     * @throws RouteNotFoundException
     * @throws RouteNotAcceptedException
     * @return mixed[]
     */
    private function findRouteFromRequest()
    {
        $routes = $this->routes[$this->requestedMethod];
        $matchingRoutes = [];
        foreach ($routes as $route) {
            if ($route['route']->match($this->requestedUri)) {
                $matchingRoutes[] = $route;
            }
        }
        if (empty($matchingRoutes)) {
            throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
        }
        foreach ($matchingRoutes as $route) {
            if ($this->isRequestAcceptedForRoute($route)) {
                return $route;
            }
        }

        throw new RouteNotAcceptedException($this->request->getAccept());
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
        if (is_array($acceptedFormats)) {
            foreach ($acceptedFormats as $format) {
                if (in_array($format, $this->requestedRepresentations)) {
                    return true;
                }
            }
            return false;
        }
        return in_array($acceptedFormats, $this->requestedRepresentations);
    }

    /**
     * Prepare the response which will be sent to the client once the
     * specified callback function for the route has been executed. Make
     * sure to load the GET parameters which could be used inside the
     * callback function (through method loadMissingRequestParameters).
     *
     * @param mixed[] $route
     */
    private function prepareResponse($route)
    {
        $this->beforeCallback($route);
        $response = $this->createResponse($route);
        if (!is_null($response)) {
            $response->send();
        }
        $this->afterCallback($route);
    }

    /**
     * Creates the response to return while executing the before and after
     * methods if they are available.
     *
     * @param $route
     * @return Response | null
     */
    private function createResponse($route): ?Response
    {
        $controller = $this->getRouteControllerInstance($route);
        if (!is_null($controller)) {
            $responseBefore = $controller->before();
            if (!is_null($responseBefore) && $responseBefore instanceof Response) {
                return $responseBefore;
            }
        }

        $values = $route['route']->getArguments($this->requestedUri);
        $this->loadRequestParameters($values);
        $callback = new Callback($route['callback']);
        $arguments = $this->getFunctionArguments($callback->getReflection(), array_values($values));
        $response = $callback->executeArray($arguments);

        if (!is_null($controller)) {
            $responseAfter = $controller->after($response);
            if (!is_null($responseAfter) && $responseAfter instanceof Response) {
                return $responseAfter;
            }
        }
        return $response;
    }

    /**
     * Retrieves the controller instance provided as the route action.
     *
     * @param $route
     * @return Controller | null
     */
    private function getRouteControllerInstance($route): ?Controller
    {
        return (is_array($route['callback'])) ? $route['callback'][0] : null;
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param $values
     * @return array
     */
    private function getFunctionArguments(ReflectionFunctionAbstract $reflection, $values)
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
     * @param mixed[] $values
     */
    private function loadRequestParameters($values)
    {
        foreach ($values as $param => $value) {
            $this->request->prependParameter($param, $value);
        }
    }

    /**
     * Verify if the HTTP method used in the request is valid (GET, PATCH,
     * POST, DELETE, PUT) and check if the method has at least one route
     * specified (through the veryMethodDefinition method). An exception
     * is thrown if one of these conditions are not satisfied.
     *
     * @throws RouteMethodUnsupportedException
     * @throws RouteNotFoundException
     */
    private function verifyRequestMethod()
    {
        if (!in_array($this->requestedMethod, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            throw new RouteMethodUnsupportedException($this->requestedMethod);
        }
        if (!$this->isRequestedMethodHasDefinitions()) {
            throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
        }
    }

    /**
     * Verify if the method has at least one route specified. An
     * exception is thrown otherwise.
     */
    private function isRequestedMethodHasDefinitions()
    {
        return array_key_exists($this->requestedMethod, $this->routes);
    }
}
