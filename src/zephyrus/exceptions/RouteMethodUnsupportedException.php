<?php namespace Zephyrus\Exceptions;

class RouteMethodUnsupportedException extends \Exception
{
    private $method;

    public function __construct($method)
    {
        parent::__construct("The specified method [{$method}] is not supported");
        $this->method = $method;
    }

    public function getMethod()
    {
        return $this->method;
    }
}