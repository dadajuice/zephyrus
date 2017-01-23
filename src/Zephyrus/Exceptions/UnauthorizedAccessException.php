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
        parent::__construct((!empty($uri) && !empty($requirements))
            ? "Unauthorized access! Requirement(s) [$data] failed for route [$uri]"
            : "Unauthorized access!");
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
