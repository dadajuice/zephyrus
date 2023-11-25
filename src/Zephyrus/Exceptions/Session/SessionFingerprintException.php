<?php namespace Zephyrus\Exceptions\Session;

class SessionFingerprintException extends SessionException
{
    public function __construct()
    {
        parent::__construct("The session fingerprint is invalid and thus the session cannot be started.", 13007);
    }
}
