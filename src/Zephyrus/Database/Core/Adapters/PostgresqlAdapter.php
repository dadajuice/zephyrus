<?php namespace Zephyrus\Database\Core\Adapters;

class PostgresqlAdapter extends DatabaseAdapter
{
    const DBMS = ["pgsql"];

    public function getLimitClause(int $offset, int $maxEntities): string
    {
        return " LIMIT $maxEntities OFFSET $offset";
    }

    public function getSearchFieldClause(string $field, string $search): string
    {
        $search = $this->purify($search);
        return "($field ILIKE '%$search%')";
    }

    // myapp.user
    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "set session \"$name\" = '$value';";
    }
}
