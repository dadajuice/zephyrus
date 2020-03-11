<?php namespace Zephyrus\Database\Adapters;

class MysqlAdapter extends DatabaseAdapter
{
    public function getLimitClause(int $offset, int $maxEntities): string
    {
        return " LIMIT $offset, $maxEntities";
    }

    public function addSessionVariable(string $name, string $value)
    {
        $this->database->query("SET @$name = ?", [$value]);
    }
}
