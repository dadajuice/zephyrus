<?php namespace Zephyrus\Exceptions\Security;

use Zephyrus\Network\HttpMethod;

abstract class CsrfException extends SecurityException
{
    protected HttpMethod $method;
    protected string $route;

    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    public function getRoute(): string
    {
        return $this->route;
    }
}
