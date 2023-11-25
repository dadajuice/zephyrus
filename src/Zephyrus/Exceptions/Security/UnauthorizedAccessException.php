<?php namespace Zephyrus\Exceptions\Security;

use Zephyrus\Network\HttpMethod;

class UnauthorizedAccessException extends SecurityException
{
    private array $requirements;
    private string $route;
    private HttpMethod $method;

    public function __construct(HttpMethod $method, string $route, array $requirements = [])
    {
        $this->method = $method;
        $this->route = $route;
        $this->requirements = $requirements;
        $data = implode(', ', $requirements);
        parent::__construct((!empty($uri) && !empty($requirements))
            ? "Unauthorized access! Requirement(s) [$data] failed for route [$uri]"
            : "Unauthorized access!", 14004);
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getMethod(): HttpMethod
    {
        return $this->method;
    }
}
