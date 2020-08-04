<?php namespace Zephyrus\Application;

class Route
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $regex = null;

    /**
     * @var array
     */
    private $parameters = [];

    public function __construct(string $routeDefinition)
    {
        if ($routeDefinition != '/') {
            $routeDefinition = rtrim($routeDefinition, '/');
        }
        $this->parameters = $this->getUriParameters($routeDefinition);
        if (!empty($this->parameters) && count($this->parameters) != count(array_unique($this->parameters))) {
            throw new \InvalidArgumentException("Route [{$routeDefinition}] cannot be added since you have at
                                                 least one duplicate parameter. Each parameter must 
                                                 have a unique identifier.");
        }
        $this->uri = $routeDefinition;
        $this->regex = $this->getUriRegexFromParameters($routeDefinition, $this->parameters);
    }

    /**
     * Verifies if the given uri matches the route definition.
     *
     * @param string $uri
     * @return bool
     */
    public function match(string $uri): bool
    {
        if ($this->uri == $uri) {
            return true;
        }
        $pattern = '/^' . $this->regex . '$/';
        return !is_null($this->regex) && preg_match($pattern, $uri);
    }

    /**
     * Retrieves all uri arguments matching the given uri.
     *
     * @param string $uri
     * @param null $callback
     * @return array
     */
    public function getArguments(string $uri, $callback = null): array
    {
        $values = [];
        $pattern = '/^' . $this->regex . '$/';
        preg_match_all($pattern, $uri, $matches);
        $matchCount = count($matches);
        for ($i = 1; $i < $matchCount; ++$i) {
            $values[] = (!is_null($callback))
                ? (new Callback($callback))->execute($matches[$i][0])
                : $matches[$i][0];
        }
        $arguments = [];
        $i = 0;
        foreach ($this->parameters as $parameter) {
            $arguments[$parameter] = $values[$i];
            ++$i;
        }
        return $arguments;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Retrieves all parameters from the specified $uri. A valid parameter
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
