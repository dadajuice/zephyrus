<?php namespace Zephyrus\Exceptions;

class InvalidCsrfException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Invalid CSRF token supplied");
    }
}
