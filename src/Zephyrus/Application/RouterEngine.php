<?php namespace Zephyrus\Application;

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
     * Launch the routing process to determine, according to the
     * initiated request, the best route to execute. Cannot be overridden.
     *
     * @param Request $request
     * @throws \Exception
     */
    final public function run(Request $request)
    {
        $this->request = $request;
        $this->requestedUri = $this->request->getUri()->getPath();
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
     * @throws \Exception
     * @return mixed[]
     */
    private function findRouteFromRequest()
    {
        $routes = $this->routes[$this->requestedMethod];
        foreach ($routes as $route) {
            if ($route['route']->match($this->requestedUri)) {
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
     * @throws \Exception
     * @return Response | null
     */
    private function createResponse($route): ?Response
    {
        $controller = $this->getRouteControllerInstance($route);
        if (!is_null($controller)) {
            $responseBefore = $controller->before();
            if ($responseBefore instanceof Response) {
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
            if ($responseAfter instanceof Response) {
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
     * @param mixed[] $values
     * @throws \Exception
     */
    private function loadRequestParameters($values)
    {
        foreach ($values as $param => $value) {
            $this->request->prependParameter($param, $value);
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
}
