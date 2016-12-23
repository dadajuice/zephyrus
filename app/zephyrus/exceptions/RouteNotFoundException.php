<?php namespace Zephyrus\Exceptions;

class RouteNotFoundException extends \Exception
{
    private $uri;
    private $method;

    public function __construct($uri, $method)
    {
        parent::__construct("The specified route [{$uri}] has not been defined for method [{$method}]");
        $this->uri = $uri;
        $this->method = $method;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }
}