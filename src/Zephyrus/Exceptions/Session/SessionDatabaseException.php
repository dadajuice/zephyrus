<?php namespace Zephyrus\Exceptions\Session;

abstract class SessionDatabaseException extends SessionException
{
    protected string $table;
    protected string $schema;

    public function __construct(string $table, string $schema, string $message, int $code)
    {
        $this->table = $table;
        $this->schema = $schema;
        parent::__construct($message, $code);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }
}
