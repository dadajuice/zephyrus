<?php namespace Zephyrus\Exceptions\Session;

class SessionUseOnlyCookiesException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Session configurations are not secure. Fixation may be possible because the session identifier is accessible through the GET parameters. Please review your php.ini or local settings for directive session.use_only_cookies.", 13006);
    }
}