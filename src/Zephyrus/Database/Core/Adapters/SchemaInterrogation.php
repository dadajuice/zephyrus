<?php namespace Zephyrus\Database\Core\Adapters;

use stdClass;
use Zephyrus\Database\Core\Database;

interface SchemaInterrogation
{
    /**
     * Meta query to retrieve all table names of given database instance. Must be redefined in children adapter classes
     * to adapt for each supported DBMS. Should return only an array with the table names as value (e.g. ['user',
     * 'client']).
     *
     * @param Database $database
     * @return string[]
     */
    public function getAllTableNames(Database $database): array;

    /**
     * Meta query to retrieve all table details of given database instance. Must be redefined in children adapter
     * classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @return stdClass[]
     */
    public function getAllTables(Database $database): array;

    /**
     * Meta query to retrieve all column names of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return only an array with the
     * columns names as value (e.g. ['firstname', 'lastname']).
     *
     * @param Database $database
     * @param string $tableName
     * @return string[]
     */
    public function getAllColumnNames(Database $database, string $tableName): array;

    /**
     * Meta query to retrieve all contraints of a specific table. Must be redefined in children adapter classes to
     * adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @param string $tableName
     * @return stdClass[]
     */
    public function getAllConstraints(Database $database, string $tableName): array;

    /**
     * Meta query to retrieve all column details of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @param string $tableName
     * @return stdClass[]
     */
    public function getAllColumns(Database $database, string $tableName): array;
}
