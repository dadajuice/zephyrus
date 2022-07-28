<?php namespace Zephyrus\Database\Core\Adapters\Mysql;

use Zephyrus\Database\Core\Adapters\SchemaInterrogator;
use Zephyrus\Database\Core\Database;

class MysqlSchemaInterrogator extends SchemaInterrogator
{
    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getAllTableNames(): array
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = schema()";
        $statement = $this->database->query($sql);
        $results = [];
        while ($row = $statement->next()) {
            $results[] = $row->table_name;
        }
        return $results;
    }

    /**
     * @codeCoverageIgnore
     * @param string $tableName
     * @return array
     */
    public function getAllColumnNames(string $tableName): array
    {
        $columns = [];
        $statement = $this->database->query("SHOW FIELDS FROM $tableName");
        while ($row = $statement->next()) {
            $columns[] = $row->Field;
        }
        return $columns;
    }

    /**
     * @codeCoverageIgnore
     * @param string $tableName
     * @return array
     */
    public function getAllConstraints(string $tableName): array
    {
        $constraints = [];
        $sql = "SHOW FIELDS FROM $tableName";
        $statement = $this->database->query($sql, [$tableName]);
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

    /**
     * @codeCoverageIgnore
     * @param string $tableName
     * @return array
     */
    public function getAllColumns(string $tableName): array
    {
        $columns = [];
        $statement = $this->database->query("SHOW FIELDS FROM $tableName");
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
