<?php namespace Zephyrus\Exceptions\Session;

class SessionDatabaseTableException extends SessionDatabaseException
{
    public function __construct(string $table, string $schema = 'public')
    {
        parent::__construct($table, $schema, $this->buildMessage($table, $schema), 13001);
    }

    private function buildMessage(string $table, string $schema): string
    {
        return "The configured session table [$schema.$table] doesn't exist.";
    }
}
