<?php namespace Zephyrus\Exceptions;

class DatabaseException extends \RuntimeException
{
    private string $query;

    public function __construct(string $message, string $query = "")
    {
        parent::__construct($message);
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
