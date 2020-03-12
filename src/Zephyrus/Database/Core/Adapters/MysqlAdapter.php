<?php namespace Zephyrus\Database\Core\Adapters;

class MysqlAdapter extends DatabaseAdapter
{
    const DBMS = ["mysql"];

    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "SET @$name = '$value'";
    }
}
