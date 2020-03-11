<?php namespace Zephyrus\Database\Adapters;

class PostgresqlAdapter extends DatabaseAdapter
{
    public function getLimitClause(int $offset, int $maxEntities): string
    {
        return " LIMIT $maxEntities OFFSET $offset";
    }

    // myapp.user
    public function addSessionVariable(string $name, string $value)
    {
        $this->database->query("set session \"$name\" = '" . $value . "';");
    }
}
