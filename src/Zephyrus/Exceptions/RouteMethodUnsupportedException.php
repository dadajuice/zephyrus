<?php namespace Zephyrus\Exceptions;

class RouteMethodUnsupportedException extends ZephyrusRuntimeException
{
    private string $method;

    public function __construct(string $method)
    {
        parent::__construct("The specified HTTP method [$method] is not supported.");
        $this->method = $method;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
