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
    public function getAllTableNames(): array
    {
        $sql = "SELECT tables.table_name FROM information_schema.tables WHERE tables.table_schema = 'public' AND tables.table_name != 'schema_version'";
        $statement = $this->database->query($sql);
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
     * @return string[]
     */
    public function getAllColumnNames(string $tableName): array
    {
        $columns = [];
        $statement = $this->database->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?", [$tableName]);
        while ($row = $statement->next()) {
            $columns[] = $row->column_name;
        }
        return $columns;
    }

    /**
     * Meta query to retrieve all contraints of a specific table. Must be redefined in children adapter classes to
     * adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param string $tableName
     * @return stdClass[]
     */
    public function getAllConstraints(string $tableName): array
    {
        $constraints = [];
        $sql = "SELECT tco.constraint_type, kcu.column_name
                  FROM information_schema.table_constraints tco
                  JOIN information_schema.key_column_usage kcu
                    ON kcu.constraint_name = tco.constraint_name
                   AND kcu.constraint_schema = tco.constraint_schema
                   AND kcu.constraint_name = tco.constraint_name
                 WHERE kcu.table_name = ?
                   AND kcu.table_schema = 'public'";
        $statement = $this->database->query($sql, [$tableName]);
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
     * @param string $tableName
     * @return stdClass[]
     */
    public function getAllColumns(string $tableName): array
    {
        $columns = [];
        $sql = "SELECT column_name, is_nullable, udt_name, character_maximum_length, column_default 
                  FROM information_schema.columns 
                 WHERE table_schema = 'public' 
                   AND table_name = ?";
        $statement = $this->database->query($sql, [$tableName]);
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

    /**
     * Meta query to retrieve all table details of given database instance. Return an array of stdClasses with the
     * following properties: name, columns and contraints.
     *
     * @return stdClass[]
     */
    public function getAllTables(): array
    {
        $results = [];
        foreach ($this->getAllTableNames() as $name) {
            $results[] = (object) [
                'name' => $name,
                'columns' => $this->getAllColumns($name),
                'constraints' => $this->getAllConstraints($name)
            ];
        }
        return $results;
    }
}
