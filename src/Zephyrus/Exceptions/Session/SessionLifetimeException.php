<?php namespace Zephyrus\Exceptions\Session;

class SessionLifetimeException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Session lifetime configuration property must be int (seconds before expiration, defaults to 1440).", 13011);
    }
}
