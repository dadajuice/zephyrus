<?php namespace Zephyrus\Network\Router;

use InvalidArgumentException;
use Zephyrus\Application\Callback;
use Zephyrus\Application\Controller;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;

class RouteDefinition
{
    private string $rawRouteRoot;
    private string $routeRoot;
    private string $route;
    private ?string $controllerClass = null;
    private ?string $controllerMethod = null;
    private mixed $callback = null;
    private array $acceptedContentTypes = [ContentType::ANY];
    private string $regexPattern;
    private array $argumentDefinitions; // List of URL arguments (e.g. id)
    private array $arguments = []; // List of key - value for the loaded arguments (e.g. id => 4)
    private array $authorizationRules = [];
    private bool $strictRuleInterpretation = false;

    /**
     * Initiates a route definition from the given URL (which can include arguments, e.g. /products/{id}).
     *
     * @param string $route
     */
    public function __construct(string $route, string $routeRoot = "")
    {
        $this->rawRouteRoot = ($routeRoot == "/") ? $routeRoot : rtrim($routeRoot, "/");;
        $this->route = ($route == "/") ? $route : rtrim($route, "/");
        $this->initializeUrlArguments();
        $this->initializeRegexPattern();
    }

    /**
     * Verifies if the given url matches the route definition with arguments matching is any.
     *
     * @param string $url
     * @return bool
     */
    public function matchUrl(string $url): bool
    {
        if ($this->route == $url) {
            return true;
        }
        $pattern = '/^' . $this->regexPattern . '$/';
        return preg_match($pattern, $url);
    }

    /**
     * Determines if the given request is acceptable for the route.
     *
     * @param Request $request
     * @return bool
     */
    public function isAccepted(Request $request): bool
    {
        $acceptedContentTypes = $request->getAccept()->getAcceptedContentTypes();
        foreach ($this->acceptedContentTypes as $format) {
            if ($format == ContentType::ANY || in_array($format, $acceptedContentTypes)) {
                return true;
            }
        }
        return false;
    }

    public function getRouteRoot(): string
    {
        return $this->routeRoot;
    }

    public function getRawRouteRoot(): string
    {
        return $this->rawRouteRoot;
    }

    /**
     * Applies the callback to launch when the route is called.
     *
     * @param array|callable $callback
     * @return void
     */
    public function setCallback(array|callable $callback): void
    {
        if (is_array($callback)) {
            // TODO: Make sure is controller ... ?
            $this->controllerClass = $callback[0];
            $this->controllerMethod = $callback[1];
        } else {
            $this->callback = $callback;
        }
    }

    /**
     * Applies the set of rules to test if the route is authorized to be executed. Rules are defined in the
     * AuthorizationRepository class.
     *
     * @param array $rules
     * @param bool $strictInterpretation
     */
    public function setAuthorizationRules(array $rules, bool $strictInterpretation = false): void
    {
        $this->authorizationRules = $rules;
        $this->strictRuleInterpretation = $strictInterpretation;
    }

    public function getAuthorizationRules(): array
    {
        return $this->authorizationRules;
    }

    public function isStrictRuleInterpretation(): bool
    {
        return $this->strictRuleInterpretation;
    }

    public function hasCallback(): bool
    {
        return !is_null($this->callback);
    }

    public function getControllerInstance(): Controller
    {
        return new $this->controllerClass();
    }

    public function getCallback(?Controller $controllerInstance = null): Callback
    {
        return (is_null($controllerInstance)) ?
            new Callback($this->callback) :
            new Callback([$controllerInstance, $this->controllerMethod]);
    }

    public function extractArgumentsFromUrl(string $requestedUrl): void
    {
        $values = [];
        $pattern = '/^' . $this->regexPattern . '$/';
        preg_match_all($pattern, $requestedUrl, $matches);

        $matchCount = count($matches);
        for ($i = 1; $i < $matchCount; ++$i) {
            $values[] = $matches[$i][0];
        }

        $argumentValues = [];
        $i = 0;
        foreach ($this->argumentDefinitions as $argument) {
            $argumentValues[$argument] = $values[$i];
            ++$i;
        }
        $this->arguments = $argumentValues;

        if (!empty($this->rawRouteRoot)) {
            $this->routeRoot = $this->rawRouteRoot;
            foreach ($this->argumentDefinitions as $argumentName) {
                $this->routeRoot = localize($this->routeRoot, [$argumentName => $this->arguments[$argumentName]]);
            }
        }
    }

    public function getArgumentValues(): array
    {
        return array_values($this->arguments);
    }

    public function getArgumentNames(): array
    {
        return array_keys($this->arguments);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name, mixed $defaultValue = null): mixed
    {
        return $this->arguments[$name] ?? $defaultValue;
    }

    public function setArgument(string $name, mixed $value): void
    {
        $this->arguments[$name] = $value;
    }

    /**
     * Applies the supported accepted content type for the route response.
     *
     * @param array $contentTypes
     * @return void
     */
    public function setAcceptedContentTypes(array $contentTypes): void
    {
        $this->acceptedContentTypes = $contentTypes;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Retrieves all arguments from the specified $url. A valid parameter is defined inside braces (e.g. {id}). Keeps
     * the parameters ordinal order. Accept every character for parameter except "/". Each argument must be unique, an
     * InvalidArgumentException is thrown otherwise.
     */
    private function initializeUrlArguments(): void
    {
        $arguments = [];
        $pattern = '/\{([^\/]+)\}/';
        if (preg_match_all($pattern, $this->route, $results) > 0) {
            foreach ($results[1] as $result) {
                $arguments[] = $result;
            }
        }
        if (!empty($arguments) && count($arguments) != count(array_unique($arguments))) {
            throw new InvalidArgumentException("Route [$this->route] cannot be added since you have at
                                                 least one duplicate parameter. Each parameter must 
                                                 have a unique identifier.");
        }
        $this->argumentDefinitions = $arguments;
    }

    /**
     * Retrieve a regex pattern matching each parameter specified within arguments inside the provided $url. A valid
     * parameter is defined inside braces (e.g. {id}).
     */
    private function initializeRegexPattern(): void
    {
        $regex = str_replace('/', '\/', $this->route);
        foreach ($this->argumentDefinitions as $argument) {
            $regex = str_replace('{' . $argument . '}', '([^\/]+)', $regex);
        }
        $this->regexPattern = $regex;
    }
}
