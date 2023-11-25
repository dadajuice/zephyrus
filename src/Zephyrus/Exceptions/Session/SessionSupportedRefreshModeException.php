<?php namespace Zephyrus\Exceptions\Session;

class SessionSupportedRefreshModeException extends SessionException
{
    private string $refreshMode;

    public function __construct(string $refreshMode)
    {
        $this->refreshMode = $refreshMode;
        parent::__construct("The specified session refresh mode [$refreshMode] is invalid. Must be one of the following values 'none', 'probability', 'interval' or 'request'.", 13008);
    }

    public function getRefreshMode(): string
    {
        return $this->refreshMode;
    }
}
