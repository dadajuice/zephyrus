<?php namespace Zephyrus\Database\Core\Adapters\Sqlite;

use Zephyrus\Database\Core\Adapters\SchemaInterrogator;
use Zephyrus\Database\Core\Database;

class SqliteSchemaInterrogator extends SchemaInterrogator
{
    public function getAllTableNames(Database $database): array
    {
        $sql = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'";
        $statement = $database->query($sql);
        $results = [];
        while ($row = $statement->next()) {
            $results[] = $row->name;
        }
        return $results;
    }

    public function getAllConstraints(Database $database, string $tableName): array
    {
        $constraints = [];
        $statement = $database->query("PRAGMA table_info($tableName)");
        while ($row = $statement->next()) {
            if ($row->pk) {
                $constraints[] = (object) [
                    'column' => $row->name,
                    'type' => 'PRIMARY KEY'
                ];
            }
        }
        return $constraints;
    }

    public function getAllColumnNames(Database $database, string $tableName): array
    {
        $columns = [];
        $statement = $database->query("PRAGMA table_info($tableName)");
        while ($row = $statement->next()) {
            $columns[] = $row->name;
        }
        return $columns;
    }

    public function getAllColumns(Database $database, string $tableName): array
    {
        $columns = [];
        $statement = $database->query("PRAGMA table_info($tableName)");
        while ($row = $statement->next()) {
            $columns[] = (object) [
                'name' => $row->name,
                'type' => strtoupper($row->type),
                'default' => $row->dflt_value,
                'notnull' => boolval($row->notnull)
            ];
        }
        return $columns;
    }
}
