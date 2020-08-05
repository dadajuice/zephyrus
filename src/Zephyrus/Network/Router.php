<?php namespace Zephyrus\Network;

use ReflectionFunctionAbstract;
use stdClass;
use Zephyrus\Application\Callback;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;

class Router
{
    private const SUPPORTED_HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @var stdClass[] Associative array that contains all defined routes
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
     * Launch the routing process to determine, according to the initiated request, the best route to execute. Cannot be
     * overridden to ensure proper functionality.
     *
     * @param Request $request
     * @throws RouteNotFoundException
     * @throws RouteMethodUnsupportedException
     * @throws RouteNotAcceptedException
     * @throws RouteArgumentException
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
     * Returns the request as a reference. So that any further modifications would be reflected within this object.
     *
     * @return Request
     */
    final public function &getRequest()
    {
        return $this->request;
    }

    /**
     * Adds a new GET route for the application. The GET method must be used to represent a specific resource (or
     * collection) in some representational format (HTML, JSON, XML, ...). Normally, a GET request must only present
     * data and not alter them in any way.
     *
     * E.g. GET /books
     *      GET /book/{id}
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array $acceptedFormats
     */
    public function get(string $uri, $callback, $acceptedFormats = ContentType::ANY)
    {
        $this->addRoute('GET', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new POST route for the application. The POST method must be used to create a new entry in a collection. It
     * is rarely used on a specific resource.
     *
     * E.g. POST /books
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array $acceptedFormats
     */
    public function post(string $uri, $callback, $acceptedFormats = ContentType::ANY)
    {
        $this->addRoute('POST', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new PUT route for the application. The PUT method must be used to update a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. PUT /book/{id}
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array $acceptedFormats
     */
    public function put(string $uri, $callback, $acceptedFormats = ContentType::ANY)
    {
        $this->addRoute('PUT', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new PATCH route for the application. The PATCH method must be used to update a specific resource or
     * collection and must be considered idempotent. Should be used instead of PUT when it is possible to update only
     * given fields to update and not the entire resource.
     *
     * E.g. PATCH /book/{id}
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array $acceptedFormats
     */
    public function patch(string $uri, $callback, $acceptedFormats = ContentType::ANY)
    {
        $this->addRoute('PATCH', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new DELETE route for the application. The DELETE method must be used only to delete a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. DELETE /book/{id}
     *      DELETE /books
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array $acceptedFormats
     */
    public function delete(string $uri, $callback, $acceptedFormats = ContentType::ANY)
    {
        $this->addRoute('DELETE', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new route for the application. Make sure to create the adequate structure with corresponding parameters
     * regex pattern if needed.
     *
     * @param string $method
     * @param string $uri
     * @param callable $callback
     * @param string | array $acceptedFormats
     */
    private function addRoute($method, $uri, $callback, $acceptedFormats)
    {
        $this->routes[$method][] = (object) [
            'route' => new Route($uri),
            'callback' => $callback,
            'acceptedRequestFormats' => $acceptedFormats
        ];
    }

    /**
     * Finds a route corresponding to the client request. Matches direct URIs and parametrised URIs. When a match is
     * found, there's a last verification to check if the route is accepted. The first route to properly match the
     * request is then returned. Hence the need to declare routes in correct order.
     *
     * @throws RouteNotFoundException
     * @throws RouteNotAcceptedException
     * @return stdClass
     */
    private function findRouteFromRequest(): stdClass
    {
        $routes = $this->routes[$this->requestedMethod];
        $matchingRoutes = [];
        foreach ($routes as $routeDefinition) {
            if ($routeDefinition->route->match($this->requestedUri)) {
                $matchingRoutes[] = $routeDefinition;
            }
        }
        if (empty($matchingRoutes)) {
            throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
        }
        foreach ($matchingRoutes as $routeDefinition) {
            if ($this->isRequestAcceptedForRoute($routeDefinition)) {
                return $routeDefinition;
            }
        }

        throw new RouteNotAcceptedException($this->request->getAccept());
    }

    /**
     * Determines if the request is acceptable for the specified route.
     *
     * @param stdClass $route
     * @return bool
     */
    private function isRequestAcceptedForRoute(stdClass $route)
    {
        if (empty($this->requestedRepresentations)) {
            return true;
        }
        $acceptedFormats = $route->acceptedRequestFormats;
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
     * Prepares the response which will be sent to the client once the specified callback function for the route has
     * been executed. Makes sure to load the route parameters which could be used inside the callback function.
     *
     * @param stdClass $route
     * @throws RouteArgumentException
     */
    private function prepareResponse(stdClass $route)
    {
        $arguments = $route->route->getArguments($this->requestedUri);
        $this->loadRequestParameters($arguments);
        $response = $this->createResponse($route, $arguments);
        if (!is_null($response)) {
            $response->send();
        }
    }

    /**
     * Creates the response to return while executing the before and after methods if they are available from the
     * corresponding controller class.
     *
     * @param stdClass $route
     * @param array $arguments
     * @throws RouteArgumentException
     * @return Response | null
     */
    private function createResponse(stdClass $route, array $arguments): ?Response
    {
        $controller = $this->getRouteControllerInstance($route);
        $responseBefore = $this->beforeMiddleware($controller);
        if (!is_null($controller) && !empty($arguments)) {
            try {
                $this->restrictArguments($controller, $arguments);
                $this->overrideArguments($controller, $arguments);
            } catch (RouteArgumentException $exception) {
                return $controller->handleRouteArgumentException($exception);
            }
        }
        $response = $this->executeRoute($route, $arguments, $responseBefore);
        return $this->afterMiddleware($controller, $response);
    }

    /**
     * @param Controller $controller
     * @param array $arguments
     * @throws RouteArgumentException
     */
    private function restrictArguments(Controller $controller, array $arguments)
    {
        $parameterNames = array_keys($arguments);
        foreach ($controller->getRestrictedArguments() as $name => $rules) {
            if (in_array($name, $parameterNames)) {
                $value = $arguments[$name];
                foreach ($rules as $ruleId => $rule) {
                    if (!$rule->isValid($value)) {
                        throw new RouteArgumentException($name, $value, $ruleId, $rule->getErrorMessage());
                    }
                }
            }
        }
    }

    /**
     * @param Controller $controller
     * @param array $arguments
     */
    private function overrideArguments(Controller $controller, array &$arguments)
    {
        $parameterNames = array_keys($arguments);
        foreach ($controller->getOverriddenArguments() as $name => $callback) {
            if (in_array($name, $parameterNames)) {
                $arguments[$name] = $callback($arguments[$name]);
                $this->request->addParameter($name, $arguments[$name]);
            }
        }
    }

    /**
     * Executes the before method of the given controller class. If this method returns a Response instance, it will
     * break the chain of execution immediately and returns the response.
     *
     * @param Controller|null $controller
     * @return Response|null
     */
    private function beforeMiddleware(?Controller $controller): ?Response
    {
        if (!is_null($controller)) {
            $responseBefore = $controller->before();
            if (!is_null($responseBefore) && $responseBefore instanceof Response) {
                return $responseBefore;
            }
        }
        return null;
    }

    /**
     * Executes the route aimed by the client request. At first it will verify if the before callback has returned a
     * Response. If its the case the chain of execution is immediately broke and this method returns the obtained
     * previous response.
     *
     * @param stdClass $route
     * @param array $arguments
     * @param Response | null $previousResponse
     * @return Response | null
     */
    private function executeRoute(stdClass $route, array $arguments, ?Response $previousResponse): ?Response
    {
        if ($previousResponse instanceof Response) {
            return $previousResponse;
        }
        $callback = new Callback($route->callback);
        $arguments = $this->getFunctionArguments($callback->getReflection(), array_values($arguments));
        return $callback->executeArray($arguments);
    }

    /**
     * Executes the after method of the given controller class. Previous response can be null if the route callback
     * doesn't produce any proper response. E.g. doing only echo or var_dump, etc.
     *
     * @param Controller|null $controller
     * @param Response $previousResponse
     * @return Response|null
     */
    private function afterMiddleware(?Controller $controller, ?Response $previousResponse): ?Response
    {
        if (!is_null($controller)) {
            $responseAfter = $controller->after($previousResponse);
            if (!is_null($responseAfter) && $responseAfter instanceof Response) {
                return $responseAfter;
            }
        }
        return $previousResponse;
    }

    /**
     * Retrieves the controller instance provided as the route action.
     *
     * @param $route
     * @return Controller | null
     */
    private function getRouteControllerInstance(stdClass $route): ?Controller
    {
        return (is_array($route->callback)) ? $route->callback[0] : null;
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array $values
     * @return array
     */
    private function getFunctionArguments(ReflectionFunctionAbstract $reflection, array $values)
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
     * @param array $values
     */
    private function loadRequestParameters(array $values)
    {
        foreach ($values as $param => $value) {
            $this->request->prependParameter($param, $value);
        }
    }

    /**
     * Verifies if the HTTP method used in the request is valid (GET, PATCH, POST, DELETE, PUT) and check if the method
     * has at least one route specified (through the veryMethodDefinition method). An exception is thrown if one of
     * these conditions are not satisfied.
     *
     * @throws RouteMethodUnsupportedException
     * @throws RouteNotFoundException
     */
    private function verifyRequestMethod()
    {
        if (!in_array($this->requestedMethod, self::SUPPORTED_HTTP_METHODS)) {
            throw new RouteMethodUnsupportedException($this->requestedMethod);
        }
        if (!array_key_exists($this->requestedMethod, $this->routes)) {
            throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
        }
    }
}
