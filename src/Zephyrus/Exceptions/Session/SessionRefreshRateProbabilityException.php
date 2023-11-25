<?php namespace Zephyrus\Exceptions\Session;

class SessionRefreshRateProbabilityException extends SessionException
{
    public function __construct()
    {
        parent::__construct("Refresh rate must be between 0 and 100 (percentage) for probability mode.", 13010);
    }
}
