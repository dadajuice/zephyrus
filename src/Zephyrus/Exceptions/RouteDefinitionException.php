<?php

namespace Zephyrus\Exceptions;

class RouteDefinitionException extends \Exception
{
    private $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
        parent::__construct("Route [{$uri}] cannot be added since you have at
        least one duplicate parameter. Each parameter must have a unique
        identifier.");
    }
}
