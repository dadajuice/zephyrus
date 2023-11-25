<?php namespace Zephyrus\Exceptions\Session;

class SessionRefreshRateException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Session refresh rate must be positive int value.", 13009);
    }
}
