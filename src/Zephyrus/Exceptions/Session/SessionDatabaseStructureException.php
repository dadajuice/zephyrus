<?php namespace Zephyrus\Exceptions\Session;

class SessionDatabaseStructureException extends SessionDatabaseException
{
    public function __construct(string $table, string $schema = 'public')
    {
        parent::__construct($table, $schema, $this->buildMessage($table, $schema), 13002);
    }

    private function buildMessage(string $table, string $schema): string
    {
        return "The configured session table [$schema.$table] doesn't have the required columns (session_id, access and data).";
    }
}
