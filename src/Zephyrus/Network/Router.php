<?php namespace Zephyrus\Network;

use ReflectionFunctionAbstract;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\Security\IntrusionDetectionException;
use Zephyrus\Exceptions\Security\InvalidCsrfException;
use Zephyrus\Exceptions\Security\MissingCsrfException;
use Zephyrus\Exceptions\Security\UnauthorizedAccessException;
use Zephyrus\Network\Router\RouteDefinition;
use Zephyrus\Network\Router\RouteRepository;

class Router
{
    private HttpMethod $requestedMethod;
    private string $routeUrl;
    private Request $request;
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
     * @throws RouteNotAcceptedException
     * @throws RouteNotFoundException
     * @throws IntrusionDetectionException
     * @throws InvalidCsrfException
     * @throws MissingCsrfException
     * @throws UnauthorizedAccessException
     * @return Response|null
     */
    final public function resolve(Request $request): ?Response
    {
        $this->request = $request;
        $this->routeUrl = $request->getUrl()->getPath();
        $this->routeUrl = ($this->routeUrl == "/") ? $this->routeUrl : rtrim($this->routeUrl, "/");
        $this->requestedMethod = $request->getMethod();
        $route = $this->findRouteFromRequest();
        $route->extractArgumentsFromUrl($this->routeUrl);
        $request->setRouteDefinition($route);
        $request->guard();
        return $this->createResponse($route);
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
     * @return RouteDefinition
     */
    private function findRouteFromRequest(): RouteDefinition
    {
        $matchingRoutes = $this->routeRepository->findRoutes($this->requestedMethod, $this->routeUrl);
        if (empty($matchingRoutes)) {
            throw new RouteNotFoundException($this->routeUrl, $this->requestedMethod->value);
        }
        foreach ($matchingRoutes as $routeDefinition) {
            if ($routeDefinition->isAccepted($this->request)) {
                return $routeDefinition;
            }
        }

        throw new RouteNotAcceptedException($this->request->getAccept()->getAccept());
    }

    /**
     * Creates the response to return while executing the before and after methods if they are available from the
     * corresponding controller class.
     *
     * @param RouteDefinition $route
     * @throws RouteArgumentException
     * @return Response | null
     */
    private function createResponse(RouteDefinition $route): ?Response
    {
        if ($route->hasCallback()) {
            $callback = $route->getCallback();
            $arguments = $this->getFunctionArguments($callback->getReflection(), $route->getArgumentValues());
            return $callback->executeArray($arguments);
        }

        $controller = $route->getControllerInstance();
        $controller->setRequest($this->request);

        $responseBefore = $this->beforeMiddleware($controller);
        if ($responseBefore instanceof Response) {
            return $responseBefore;
        }

        try {
            $this->restrictArguments($controller, $route);
            $response = $this->overrideArguments($controller, $route);
            if (!is_null($response)) {
                return $response;
            }
        } catch (RouteArgumentException $exception) {
            return $controller->handleRouteArgumentException($exception);
        }

        $callback = $route->getCallback($controller);
        $arguments = $this->getFunctionArguments($callback->getReflection(), $route->getArgumentValues());
        $response = $callback->executeArray($arguments);
        return $this->afterMiddleware($controller, $response);
    }

    /**
     * @param Controller $controller
     * @param RouteDefinition $route
     * @throws RouteArgumentException
     */
    private function restrictArguments(Controller $controller, RouteDefinition $route): void
    {
        $parameterNames = $route->getArgumentNames();
        foreach ($controller->getRestrictedArguments() as $name => $rules) {
            if (in_array($name, $parameterNames)) {
                $value = $route->getArgument($name);
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
     * @param RouteDefinition $route
     * @return Response|null
     */
    private function overrideArguments(Controller $controller, RouteDefinition $route): ?Response
    {
        $argumentNames = $route->getArgumentNames();
        foreach ($controller->getOverrideCallbacks() as $name => $callback) {
            if (in_array($name, $argumentNames)) {
                $overrideResult = $callback($route->getArgument($name));
                if ($overrideResult instanceof Response) {
                    return $overrideResult;
                }
                $route->setArgument($name, $overrideResult);
            }
        }
        return null;
    }

    /**
     * Executes the before method of the given controller class. If this method returns a Response instance, it will
     * break the chain of execution immediately and returns the response.
     *
     * @param Controller $controller
     * @return Response|null
     */
    private function beforeMiddleware(Controller $controller): ?Response
    {
        $responseBefore = $controller->before();
        if ($responseBefore instanceof Response) {
            return $responseBefore;
        }
        return null;
    }

    /**
     * Executes the after method of the given controller class. Previous response can be null if the route callback
     * doesn't produce any proper response. E.g. doing only echo or var_dump, etc.
     *
     * @param Controller $controller
     * @param Response|null $previousResponse
     * @return Response|null
     */
    private function afterMiddleware(Controller $controller, ?Response $previousResponse): ?Response
    {
        $responseAfter = $controller->after($previousResponse);
        if ($responseAfter instanceof Response) {
            return $responseAfter;
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
}
