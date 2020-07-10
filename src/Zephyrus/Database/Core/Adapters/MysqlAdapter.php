<?php namespace Zephyrus\Database\Core\Adapters;

use Zephyrus\Database\Core\Database;

class MysqlAdapter extends DatabaseAdapter
{
    const DBMS = ["mysql"];

    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "SET @$name = '$value'";
    }

    public function getAllTableNames(Database $database): array
    {
        $names = [];
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = schema()";
        $statement = $database->query($sql);
        $results = [];
        while ($row = $statement->next()) {
            $results[] = $row->table_name;
        }
        return $results;
    }

    public function getAllColumnNames(Database $database, string $tableName): array
    {
        $columns = [];
        $statement = $database->query("SHOW FIELDS FROM $tableName");
        while ($row = $statement->next()) {
            $columns[] = $row->Field;
        }
        return $columns;
    }

    public function getAllConstraints(Database $database, string $tableName): array
    {
        $constraints = [];
        $sql = "SHOW FIELDS FROM $tableName";
        $statement = $database->query($sql, [$tableName]);
        while ($row = $statement->next()) {
            if ($row->Key == 'PRI' || $row->Key == 'MUL') {
                $constraints[] = (object) [
                    'column' => $row->Field,
                    'type' => ($row->Key == 'PRI') ? 'PRIMARY KEY' : 'FOREIGN KEY'
                ];
            }
        }
        return $constraints;
    }

    public function getAllColumns(Database $database, string $tableName): array
    {
        $columns = [];
        $statement = $database->query("SHOW FIELDS FROM $tableName");
        while ($row = $statement->next()) {
            $columns[] = (object) [
                'name' => $row->Field,
                'type' => strtoupper($row->Type),
                'default' => $row->Default,
                'notnull' => $row->Null == "YES"
            ];
            $columns[] = $row;
        }
        return $columns;
    }
}
