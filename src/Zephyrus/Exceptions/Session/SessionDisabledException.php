<?php namespace Zephyrus\Exceptions\Session;

class SessionDisabledException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Session is disable within the PHP configurations and thus cannot be started.", 13004);
    }
}
