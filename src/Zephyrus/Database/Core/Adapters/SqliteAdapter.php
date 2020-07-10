<?php namespace Zephyrus\Database\Core\Adapters;

use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\DatabaseException;

class SqliteAdapter extends DatabaseAdapter
{
    const DBMS = ["sqlite", "sqlite2"];

    public function buildHandle(): \PDO
    {
        if (!empty($this->getDatabaseName())) {
            $path = ROOT_DIR . DIRECTORY_SEPARATOR . $this->getDatabaseName();
            if (!file_exists($path)) {
                throw new DatabaseException("The specified SQLite database file [$path] doesn't exists");
            }
        }
        return parent::buildHandle();
    }

    protected function buildDataSourceName(): string
    {
        $dsnPrefix = $this->getDatabaseManagementSystem() . ':';
        return $dsnPrefix . ((!empty($this->getDatabaseName()))
                ? ROOT_DIR . DIRECTORY_SEPARATOR . $this->getDatabaseName()
                : ':memory:');
    }

    /**
     * Non-existing feature in SQLite database.
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "";
    }

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
