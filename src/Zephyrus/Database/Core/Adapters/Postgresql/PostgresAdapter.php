<?php namespace Zephyrus\Database\Core\Adapters\Postgresql;

use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Database\Core\Adapters\SchemaInterrogator;
use Zephyrus\Database\Core\Database;

class PostgresAdapter extends DatabaseAdapter
{
    // TODO: DIE ?
    public function getLimitClause(int $offset, int $maxEntities): string
    {
        return " LIMIT $maxEntities OFFSET $offset";
    }

    /**
     * For postgresql the name must follow a prefix convention such as "myapp.var".
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    public function getSqlAddVariable(string $name, string $value): string
    {
        return "set session \"$name\" = '$value';";
    }

    public function buildSchemaInterrogator(Database $database): SchemaInterrogator
    {
        return new PostgresSchemaInterrogator($database);
    }
}
