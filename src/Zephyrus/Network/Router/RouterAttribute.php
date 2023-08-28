<?php namespace Zephyrus\Network\Router;

use Zephyrus\Network\ContentType;

abstract class RouterAttribute
{
    private string $route;
    private string $method;
    private string|array $acceptedFormats;

    public function __construct(string $method, string $route, string|array $acceptedFormats = ContentType::ANY)
    {
        $this->method = $method;
        $this->route = $route;
        $this->acceptedFormats = $acceptedFormats;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAcceptedFormats(): string|array
    {
        return $this->acceptedFormats;
    }
}
