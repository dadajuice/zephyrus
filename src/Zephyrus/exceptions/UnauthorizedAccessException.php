<?php namespace Zephyrus\Exceptions;

class UnauthorizedAccessException extends \Exception
{
    public $requirements = [];
    public $uri;

    public function __construct($uri = "", $requirements = [])
    {
        $this->uri = $uri;
        $this->requirements = $requirements;
        $data = implode(', ', $requirements);
        if (!empty($uri) && !empty($requirements)) {
            parent::__construct("Unauthorized access! Requirement(s) [$data] failed for route [$uri]");
        } else {
            parent::__construct("Unauthorized access!");
        }
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function getUri()
    {
        return $this->uri;
    }
}