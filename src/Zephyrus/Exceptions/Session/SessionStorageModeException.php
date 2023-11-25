<?php namespace Zephyrus\Exceptions\Session;

class SessionStorageModeException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Session storage configuration property must be either file or database.", 13012);
    }
}
