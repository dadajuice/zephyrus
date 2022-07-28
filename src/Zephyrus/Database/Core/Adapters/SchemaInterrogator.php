<?php namespace Zephyrus\Database\Core\Adapters;

use stdClass;
use Zephyrus\Database\Core\Database;

/**
 * Template abstract class used for all supported database management systems to interact with the meta database. This
 * class should have a specific children for each DBMS adapter with redefined methods matching the proper SQL queries
 * for each meta database's supported interrogations.
 */
abstract class SchemaInterrogator
{
    protected Database $database;

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
    public abstract function getAllTableNames(): array;

    /**
     * Meta query to retrieve all column names of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return only an array with the
     * columns names as value (e.g. ['firstname', 'lastname']).
     *
     * @param string $tableName
     * @return string[]
     */
    public abstract function getAllColumnNames(string $tableName): array;

    /**
     * Meta query to retrieve all contraints of a specific table. Must be redefined in children adapter classes to
     * adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param string $tableName
     * @return stdClass[]
     */
    public abstract function getAllConstraints(string $tableName): array;

    /**
     * Meta query to retrieve all column details of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param string $tableName
     * @return stdClass[]
     */
    public abstract function getAllColumns(string $tableName): array;

    /**
     * Meta query to retrieve all table details of given database instance. Return an array of stdClasses with the
     * following properties: name, columns and contraints.
     *
     * @return stdClass[]
     */
    public final function getAllTables(): array
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
