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
     * HTTP method associated with current request.
     *
     * @var string
     */
    private string $requestedMethod;

    /**
     * Complete uri request.
     *
     * @var string
     */
    private string $requestedUri;

    /**
     * HTTP accept directive specified by the client.
     *
     * @var array
     */
    private array $requestedRepresentations;

    /**
     * Holds the current HTTP request that must be resolved.
     *
     * @var Request
     */
    private Request $request;

    /**
     * Contains the listing of every available routes for the application.
     *
     * @var RouteRepository
     */
    private RouteRepository $routeRepository;

    public function __construct(RouteRepository $routeRepository)
    {
        $this->routeRepository = $routeRepository;
    }

    /**
     * Launch the routing process to resolve, according to the initiated request, the best route to execute. Cannot be
     * overridden to ensure proper functionality. Will return a proper Response instance if the method is able to
     * properly resolve the requested route. Returns null otherwise.
     *
     * @param Request $request
     * @throws RouteArgumentException
     * @throws RouteMethodUnsupportedException
     * @throws RouteNotAcceptedException
     * @throws RouteNotFoundException
     * @return Response|null
     */
    final public function resolve(Request $request): ?Response
    {
        $this->request = $request;
        $path = $this->request->getUri()->getPath();
        $this->requestedUri = ($path != "/") ? rtrim($path, "/") : "/";
        $this->requestedMethod = strtoupper($this->request->getMethod());
        $this->requestedRepresentations = $this->request->getAcceptedRepresentations();
        $this->verifyRequestMethod();
        $route = $this->findRouteFromRequest();
        return $this->prepareResponse($route);
    }

    /**
     * Returns the request as a reference. So that any further modifications would be reflected within this object.
     *
     * @return Request
     */
    final public function &getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Retrieves the route repository containing all the route definition for the application.
     *
     * @return RouteRepository
     */
    final public function getRepository(): RouteRepository
    {
        return $this->routeRepository;
    }

    /**
     * Finds a route corresponding to the client request. Matches direct URIs and parametrised URIs. When a match is
     * found, there's a last verification to check if the route is accepted. The first route to properly match the
     * request is then returned. Hence, the need to declare routes in correct order.
     *
     * @throws RouteNotFoundException
     * @throws RouteNotAcceptedException
     * @return stdClass
     */
    private function findRouteFromRequest(): stdClass
    {
        $matchingRoutes = $this->routeRepository->findRoutes($this->requestedMethod, $this->requestedUri);
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
    private function isRequestAcceptedForRoute(stdClass $route): bool
    {
        if (empty($this->requestedRepresentations)) {
            return true;
        }
        $acceptedFormats = $route->acceptedRequestFormats;
        if (is_array($acceptedFormats)) {
            foreach ($acceptedFormats as $format) {
                if ($format == ContentType::ANY || in_array($format, $this->requestedRepresentations)) {
                    return true;
                }
            }
            return false;
        }
        return $acceptedFormats == ContentType::ANY || in_array($acceptedFormats, $this->requestedRepresentations);
    }

    /**
     * Prepares the response which will be sent to the client once the specified callback function for the route has
     * been executed. Makes sure to load the route parameters which could be used inside the callback function.
     *
     * @param stdClass $route
     * @throws RouteArgumentException
     * @return Response|null
     */
    private function prepareResponse(stdClass $route): ?Response
    {
        $arguments = $route->route->getArguments($this->requestedUri);
        $this->loadRequestArguments($arguments);
        return $this->createResponse($route, $arguments);
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
        // Simple callback execution
        if (!is_null($route->callback)) {
            $callback = new Callback($route->callback);
            $arguments = $this->getFunctionArguments($callback->getReflection(), array_values($arguments));
            return $callback->executeArray($arguments);
        }

        $controller = new $route->controllerClass();
        $controller->setRequest($this->request);
        $responseBefore = $this->beforeMiddleware($controller);
        if (!is_null($controller) && !empty($arguments) && is_null($responseBefore)) {
            try {
                $this->restrictArguments($controller, $arguments);
                $response = $this->overrideArguments($controller, $arguments);
                if (!is_null($response)) {
                    $responseBefore = $response;
                }
            } catch (RouteArgumentException $exception) {
                return $controller->handleRouteArgumentException($exception);
            }
        }

        if ($responseBefore instanceof Response) {
            return $responseBefore;
        }

        $callback = new Callback([$controller, $route->controllerMethod]);
        $arguments = $this->getFunctionArguments($callback->getReflection(), array_values($arguments));
        $response = $callback->executeArray($arguments);
        return $this->afterMiddleware($controller, $response);
    }

    /**
     * @param Controller $controller
     * @param array $arguments
     * @throws RouteArgumentException
     */
    private function restrictArguments(Controller $controller, array $arguments): void
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
     * Overrides the arguments for a specified route argument. If the result of the override is a Response instance, we
     * must stop the processing and return this response (mostly for error cases).
     *
     * @param Controller $controller
     * @param array $arguments
     * @return Response|null
     */
    private function overrideArguments(Controller $controller, array &$arguments): ?Response
    {
        $argumentNames = array_keys($arguments);
        foreach ($controller->getOverrideCallbacks() as $name => $callback) {
            if (in_array($name, $argumentNames)) {
                $arguments[$name] = $callback($arguments[$name]);
                if ($arguments[$name] instanceof Response) {
                    return $arguments[$name];
                }
                $this->request->addArgument($name, $arguments[$name]);
            }
        }
        return null;
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
            if ($responseBefore instanceof Response) {
                return $responseBefore;
            }
        }
        return null;
    }

    /**
     * Executes the after method of the given controller class. Previous response can be null if the route callback
     * doesn't produce any proper response. E.g. doing only echo or var_dump, etc.
     *
     * @param Controller|null $controller
     * @param Response|null $previousResponse
     * @return Response|null
     */
    private function afterMiddleware(?Controller $controller, ?Response $previousResponse): ?Response
    {
        if (!is_null($controller)) {
            $responseAfter = $controller->after($previousResponse);
            if ($responseAfter instanceof Response) {
                return $responseAfter;
            }
        }
        return $previousResponse;
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array $values
     * @return array
     */
    private function getFunctionArguments(ReflectionFunctionAbstract $reflection, array $values): array
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
     * Load arguments obtained from the route resolution as part of the request.
     *
     * @param array $values
     */
    private function loadRequestArguments(array $values): void
    {
        foreach ($values as $param => $value) {
            $this->request->addArgument($param, $value);
        }
    }

    /**
     * Verifies if the HTTP method used in the request is valid (GET, PATCH, POST, DELETE, PUT) and checks if the method
     * has at least one route specified. An exception is thrown if one of these conditions are not satisfied.
     *
     * @throws RouteMethodUnsupportedException
     * @throws RouteNotFoundException
     */
    private function verifyRequestMethod(): void
    {
        if (!in_array($this->requestedMethod, self::SUPPORTED_HTTP_METHODS)) {
            throw new RouteMethodUnsupportedException($this->requestedMethod);
        }
        if (empty($this->routeRepository->getRoutes($this->requestedMethod))) {
            throw new RouteNotFoundException($this->requestedUri, $this->requestedMethod);
        }
    }
}
