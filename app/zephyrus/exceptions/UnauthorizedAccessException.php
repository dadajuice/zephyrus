<?php namespace Zephyrus\Exceptions;

class UnauthorizedAccessException extends \Exception
{
    public $allowedRoles = [];
    public $uri;

    public function __construct($uri = "", $allowedRoles = [])
    {
        $this->uri = $uri;
        $this->allowedRoles = $allowedRoles;
        $data = implode(', ', $allowedRoles);
        if (!empty($uri) && !empty($allowedRoles)) {
            parent::__construct("Unauthorized access! Route [$uri] only available to [$data]");
        } else {
            parent::__construct("Unauthorized access!");
        }
    }

    public function getAllowedRoles()
    {
        return $this->allowedRoles;
    }

    public function getUri()
    {
        return $this->uri;
    }
}