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
    /**
     * Meta query to retrieve all table names of given database instance. Must be redefined in children adapter classes
     * to adapt for each supported DBMS. Should return only an array with the table names as value (e.g. ['user',
     * 'client']).
     *
     * @param Database $database
     * @return string[]
     */
    public abstract function getAllTableNames(Database $database): array;

    /**
     * Meta query to retrieve all column names of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return only an array with the
     * columns names as value (e.g. ['firstname', 'lastname']).
     *
     * @param Database $database
     * @param string $tableName
     * @return string[]
     */
    public abstract function getAllColumnNames(Database $database, string $tableName): array;

    /**
     * Meta query to retrieve all contraints of a specific table. Must be redefined in children adapter classes to
     * adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @param string $tableName
     * @return stdClass[]
     */
    public abstract function getAllConstraints(Database $database, string $tableName): array;

    /**
     * Meta query to retrieve all column details of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @param string $tableName
     * @return stdClass[]
     */
    public abstract function getAllColumns(Database $database, string $tableName): array;

    /**
     * Meta query to retrieve all table details of given database instance. Return an array of stdClasses with the
     * following properties: name, columns and contraints.
     *
     * @param Database $database
     * @return stdClass[]
     */
    public function getAllTables(Database $database): array
    {
        $results = [];
        foreach ($this->getAllTableNames($database) as $name) {
            $results[] = (object) [
                'name' => $name,
                'columns' => $this->getAllColumns($database, $name),
                'constraints' => $this->getAllConstraints($database, $name)
            ];
        }
        return $results;
    }
}
