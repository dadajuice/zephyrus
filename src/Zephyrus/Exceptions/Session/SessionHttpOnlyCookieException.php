<?php namespace Zephyrus\Exceptions\Session;

class SessionHttpOnlyCookieException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Session configurations are not secure. Session identifier is accessible beyond the HTTP headers. Please review your php.ini or local settings for directive session.cookie_httponly.", 13003);
    }
}
