<?php namespace Zephyrus\Database\Core;

use stdClass;

final class SchemaInterrogator
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Meta query to retrieve all table names of given database instance. Must be redefined in children adapter classes
     * to adapt for each supported DBMS. Should return only an array with the table names as value (e.g. ['user',
     * 'client']).
     *
     * @return string[]
     */
    public function getAllTableNames(string $schema = 'public'): array
    {
        $sql = "SELECT tables.table_name FROM information_schema.tables WHERE tables.table_schema = ? AND tables.table_name != 'schema_version'";
        $statement = $this->database->query($sql, [$schema]);
        $results = [];
        while ($row = $statement->next()) {
            $results[] = $row->table_name;
        }
        return $results;
    }

    /**
     * Meta query to retrieve all column names of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return only an array with the
     * columns names as value (e.g. ['firstname', 'lastname']).
     *
     * @param string $tableName
     * @param string $schema
     * @return string[]
     */
    public function getAllColumnNames(string $tableName, string $schema = 'public'): array
    {
        $columns = [];
        $statement = $this->database->query("SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?", [$schema, $tableName]);
        while ($row = $statement->next()) {
            $columns[] = $row->column_name;
        }
        return $columns;
    }

    public function columnExists(string $column, string $table, string $schema = 'public'): bool
    {
        $sql = "SELECT EXISTS (SELECT 1 
                  FROM information_schema.columns 
                 WHERE table_schema = ? AND table_name = ? AND column_name = ?) as existance";
        $statement = $this->database->query($sql, [$schema, $table, $column]);
        return $statement->next()?->existance ?? false;
    }

    public function tableExists(string $table, string $schema = 'public'): bool
    {
        $sql = "SELECT EXISTS (SELECT 1 
                  FROM information_schema.tables 
                 WHERE table_schema LIKE ? AND table_type LIKE 'BASE TABLE' AND table_name = ?) as existance";
        $statement = $this->database->query($sql, [$schema, $table]);
        return $statement->next()?->existance ?? false;
    }

    public function viewExists(string $view, string $schema = 'public'): bool
    {
        $sql = "SELECT EXISTS (SELECT 1 
                  FROM information_schema.tables 
                 WHERE table_schema LIKE ? AND table_type LIKE 'VIEW' AND table_name = ?) as existance";
        $statement = $this->database->query($sql, [$schema, $view]);
        return $statement->next()?->existance ?? false;
    }

    /**
     * Meta query to retrieve all contraints of a specific table. Must be redefined in children adapter classes to
     * adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param string $table
     * @param string $schema
     * @return stdClass[]
     */
    public function getAllConstraints(string $table, string $schema = 'public'): array
    {
        $constraints = [];
        $sql = "SELECT tco.constraint_type, kcu.column_name
                  FROM information_schema.table_constraints tco
                  JOIN information_schema.key_column_usage kcu
                    ON kcu.constraint_name = tco.constraint_name
                   AND kcu.constraint_schema = tco.constraint_schema
                   AND kcu.constraint_name = tco.constraint_name
                 WHERE kcu.table_name = ?
                   AND kcu.table_schema = ?";
        $statement = $this->database->query($sql, [$table, $schema]);
        while ($row = $statement->next()) {
            $constraints[] = (object) [
                'column' => $row->column_name,
                'type' => $row->constraint_type
            ];
        }
        return $constraints;
    }

    /**
     * Meta query to retrieve all column details of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param string $table
     * @param string $schema
     * @return stdClass[]
     */
    public function getAllColumns(string $table, string $schema = 'public'): array
    {
        $columns = [];
        $sql = "SELECT column_name, is_nullable, udt_name, character_maximum_length, column_default 
                  FROM information_schema.columns 
                 WHERE table_schema = ? 
                   AND table_name = ?";
        $statement = $this->database->query($sql, [$schema, $table]);
        while ($row = $statement->next()) {
            $columns[] = (object) [
                'name' => $row->column_name,
                'type' => strtoupper($row->udt_name) . (($row->udt_name == 'varchar') ? '(' . $row->character_maximum_length . ')' : ''),
                'default' => $row->column_default,
                'notnull' => $row->is_nullable == "YES"
            ];
        }
        return $columns;
    }

    public function getTableSize(string $table, string $schema = 'public'): int
    {
        $name = $schema . '.' . $table;
        $statement = $this->database->query("SELECT pg_total_relation_size('$name') as size");
        return $statement->next()?->size ?? 0;
    }

    /**
     * Meta query to retrieve all table details of given database instance. Return an array of stdClasses with the
     * following properties: name, size, columns and contraints.
     *
     * @param string $schema
     * @return stdClass[]
     */
    public function getAllTables(string $schema = 'public'): array
    {
        $results = [];
        foreach ($this->getAllTableNames($schema) as $name) {
            $results[] = (object) [
                'name' => $name,
                'size' => $this->getTableSize($name, $schema),
                'columns' => $this->getAllColumns($name, $schema),
                'constraints' => $this->getAllConstraints($name, $schema)
            ];
        }
        return $results;
    }
}
