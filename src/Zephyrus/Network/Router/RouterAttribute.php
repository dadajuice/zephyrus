<?php namespace Zephyrus\Network\Router;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpMethod;

abstract class RouterAttribute
{
    public const SUPPORTED_ANNOTATIONS = [Get::class, Post::class, Put::class, Patch::class, Delete::class];

    private string $route;
    private HttpMethod $method;
    private array $acceptedContentTypes;

    public function __construct(HttpMethod $method, string $route, array $acceptedContentTypes = [ContentType::ANY])
    {
        $this->method = $method;
        $this->route = $route;
        $this->acceptedContentTypes = $acceptedContentTypes;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    public function getAcceptedContentTypes(): array
    {
        return $this->acceptedContentTypes;
    }
}
