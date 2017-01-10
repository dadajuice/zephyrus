<?php namespace Zephyrus\Security;

use Zephyrus\Network\RequestFactory;

class Authorization
{
    const GET = 1;
    const POST = 2;
    const PUT = 4;
    const DELETE = 8;
    const ALL = 15;

    const MODE_BLACKLIST = 0;
    const MODE_WHITELIST = 1;

    /**
     * @var Authorization
     */
    private static $instance;

    /**
     * @var array
     */
    private $requirements = [];

    /**
     * @var int
     */
    private $mode = self::MODE_WHITELIST;

    /**
     * @var array
     */
    private $rules = [];

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addRequirement(string $name, callable $callback)
    {
        if (isset($this->requirements[$name])) {
            throw new \Exception("Requirement $name is already defined");
        }
        $this->requirements[$name] = $callback;
    }

    public function addSessionRequirement(string $name, string $key, $value = null)
    {
        if (isset($this->requirements[$name])) {
            throw new \Exception("Requirement $name is already defined");
        }
        $this->requirements[$name] = function() use($key, $value) {
            if (!isset($_SESSION[$key])) {
                return false;
            }
            if (!is_null($value) && $_SESSION[$key] != $value) {
                return false;
            }
            return true;
        };
    }

    public function addIpAddressRequirement(string $name, string $ip)
    {
        if (isset($this->requirements[$name])) {
            throw new \Exception("Requirement $name is already defined");
        }
        $this->requirements[$name] = function() use($ip) {
            return RequestFactory::create()->getClientIp() == $ip;
        };
    }

    public function protect(string $pathRegex, int $httpMethod, $requirements)
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
        if ($httpMethod & self::DELETE) {
            $methods[] = 'DELETE';
        }
        foreach ($methods as $method) {
            $this->addRule($method, $pathRegex, $requirements);
        }
    }

    public function isAuthorized(array $route, array &$failedRequirements = []): bool
    {
        $match = false;
        $uri = $route['uri'];
        foreach ($this->findRule($uri) as $requirement) {
            if (!isset($this->requirements[$requirement])) {
                throw new \Exception("The specified requirement [$requirement] has not been defined");
            }
            if (!$this->requirements[$requirement]()) {
                $failedRequirements[] = $requirement;
            }
            $match = true;
        }
        return (!$match) ? $this->mode == self::MODE_BLACKLIST : empty($failedRequirements);
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

    private function findRule(string $uri): array
    {
        $method = RequestFactory::create()->getMethod();
        if (!isset($this->rules[$method])) {
            return [];
        }
        $rulesForMethod = $this->rules[$method];
        foreach ($rulesForMethod as $path => $requirements) {
            if (preg_match('/' . str_replace('/', '\/', $path) . '/', $uri)) {
                return (is_array($requirements)) ? $requirements : [$requirements];
            }
        }
        return [];
    }

    private function addRule(string $httpMethod, string $pathRegex, $requirements)
    {
        if (!isset($this->rules[$httpMethod])) {
            $this->rules[$httpMethod] = [];
        }

        if (isset($this->rules[$httpMethod][$pathRegex])) {
            throw new \Exception("Rule already exists for $httpMethod $pathRegex");
        }

        $this->rules[$httpMethod][$pathRegex] = $requirements;
    }

    private function __construct() {}
}