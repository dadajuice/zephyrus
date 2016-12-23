<?php namespace Zephyrus\Exceptions;

class RouteNotAcceptedException extends \Exception
{
    private $accept;

    public function __construct($accept)
    {
        parent::__construct("The requested representation [{$accept}] is not available");
        $this->accept = $accept;
    }

    public function getAccept()
    {
        return $this->accept;
    }
}