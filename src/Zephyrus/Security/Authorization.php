<?php namespace Zephyrus\Security;

use Zephyrus\Application\Callback;
use Zephyrus\Application\Route;
use Zephyrus\Network\RequestFactory;

class Authorization
{
    const GET = 1;
    const POST = 2;
    const PUT = 4;
    const PATCH = 8;
    const DELETE = 16;
    const ALL = 31;

    const MODE_BLACKLIST = 0;
    const MODE_WHITELIST = 1;

    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var int
     */
    private $mode = self::MODE_BLACKLIST;

    /**
     * @var array
     */
    private $protections = [];

    public function addRule(string $name, callable $callback)
    {
        if (isset($this->rules[$name])) {
            throw new \InvalidArgumentException("Requirement $name is already defined");
        }
        $this->rules[$name] = $callback;
    }

    public function addSessionRule(string $name, string $key, $value = null)
    {
        if (isset($this->rules[$name])) {
            throw new \InvalidArgumentException("Requirement $name is already defined");
        }
        $this->rules[$name] = function () use ($key, $value) {
            return isset($_SESSION[$key]) && (is_null($value) || $_SESSION[$key] == $value);
        };
    }

    public function addIpAddressRule(string $name, string $idAddress)
    {
        if (isset($this->rules[$name])) {
            throw new \InvalidArgumentException("Requirement $name is already defined");
        }
        $this->rules[$name] = function () use ($idAddress) {
            return RequestFactory::read()->getClientIp() == $idAddress;
        };
    }

    public function protect(string $route, int $httpMethod, $rules)
    {
        foreach ($this->getProtectedMethods($httpMethod) as $method) {
            $this->addProtection($method, $route, $rules);
        }
    }

    public function isAuthorized(string $uri, array &$failedRules = []): bool
    {
        $results = $this->getCorrespondingRuleResults($uri);
        foreach ($results as $rule => $result) {
            if (!$result) {
                $failedRules[] = $rule;
            }
        }
        return (empty($results)) ? $this->mode == self::MODE_BLACKLIST : empty($failedRules);
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode)
    {
        $this->mode = $mode;
    }

    private function getCorrespondingRuleResults(string $uri): array
    {
        $method = RequestFactory::read()->getMethod();
        if (!isset($this->protections[$method])) {
            return [];
        }
        $protectionsForMethod = $this->protections[$method];
        $results = [];
        foreach ($protectionsForMethod as $pathRegex => $protection) {
            if ($protection['route']->match($uri)) {
                foreach ($protection['rules'] as $rule) {
                    if (!isset($this->rules[$rule])) {
                        throw new \RuntimeException("The specified rule [$rule] has not been defined");
                    }
                    $callback = new Callback($this->rules[$rule]);
                    $values = $protection['route']->getArguments($uri);
                    $arguments = $this->getFunctionArguments($callback->getReflection(), array_values($values));
                    $result = $callback->executeArray($arguments);
                    $results[$rule] = $result;
                }
            }
        }
        return $results;
    }

    private function addProtection(string $httpMethod, string $pathRegex, $rules)
    {
        if (!isset($this->protections[$httpMethod])) {
            $this->protections[$httpMethod] = [];
        }

        if (isset($this->protections[$httpMethod][$pathRegex])) {
            throw new \InvalidArgumentException("Rule already exists for $httpMethod $pathRegex");
        }

        $this->protections[$httpMethod][$pathRegex] = [
            'route' => new Route($pathRegex),
            'rules' => (is_array($rules)) ? $rules : [$rules]
        ];
    }

    private function getProtectedMethods(int $httpMethod)
    {
        $methods = [];
        if ($httpMethod & self::GET) {
            $methods[] = 'GET';
        }
        if ($httpMethod & self::POST) {
            $methods[] = 'POST';
        }
        if ($httpMethod & self::PUT) {
            $methods[] = 'PUT';
        }
        if ($httpMethod & self::PATCH) {
            $methods[] = 'PATCH';
        }
        if ($httpMethod & self::DELETE) {
            $methods[] = 'DELETE';
        }
        return $methods;
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
}
