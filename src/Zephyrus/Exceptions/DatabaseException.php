<?php namespace Zephyrus\Exceptions;

class DatabaseException extends \Exception
{
    /**
     * @var string
     */
    private $query;

    public function __construct($message, $query = "")
    {
        parent::__construct($message);
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }
}
