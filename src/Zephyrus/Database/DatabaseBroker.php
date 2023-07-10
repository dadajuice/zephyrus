<?php namespace Zephyrus\Database;

use stdClass;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseStatement;

abstract class DatabaseBroker
{
    private Database $database;

    /**
     * Broker constructor called by children. Simply get the database reference for further use by queries. If no
     * database is given, the registered database instance in DatabaseSession will be used.
     *
     * @param null|Database $database
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? DatabaseSession::getInstance()->getDatabase();
    }

    /**
     * Fetches the value associated with the given session variable's name. Returns NULL if not found (instead of an
     * exception).
     *
     * @param string $name
     * @return mixed
     */
    public function findSessionVariable(string $name): mixed
    {
        return $this->selectSingle("SELECT current_setting(?, true) as var", [$name])->var;
    }

    /**
     * Retrieves the current database instance used for this broker.
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Executes any type of query and simply returns the DatabaseStatement object ready to be fetched. Will throw a
     * DatabaseException (silent as a RuntimeException) is the query fails to execute.
     *
     * @param string $query
     * @param array $parameters
     * @return DatabaseStatement
     */
    protected function rawQuery(string $query, array $parameters = []): DatabaseStatement
    {
        return $this->prepareStatement($query, $parameters);
    }

    /**
     * Executes any type of query and returns the first returned element is any (useful for INSERT type query with a
     * RETURNING directive). Will throw a DatabaseException (silent as a RuntimeException) is the query fails to
     * execute.
     *
     * @param string $query
     * @param array $parameters
     * @return null|stdClass
     */
    protected function query(string $query, array $parameters = []): ?stdClass
    {
        $statement = $this->prepareStatement($query, $parameters);
        return $statement->next();
    }

    /**
     * Executes a SELECT query which should return a single data row. Best suited for queries involving primary key in
     * where. Will return null if the query did not fetch any result. Will throw a DatabaseException (silent as a
     * RuntimeException) is the query fails to execute.
     *
     * @param string $query
     * @param array $parameters
     * @return stdClass|null
     */
    protected function selectSingle(string $query, array $parameters = []): ?stdClass
    {
        $statement = $this->prepareStatement($query, $parameters);
        return $statement->next();
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will return an empty array if the query
     * did not return any results. Will throw a DatabaseException (silent as a RuntimeException) is the query fails to
     * execute.
     *
     * @param string $query
     * @param array $parameters
     * @param callable|null $callback
     * @return stdClass[]
     */
    protected function select(string $query, array $parameters = [], ?callable $callback = null): array
    {
        $result = new SqlResult($this->prepareStatement($query, $parameters));
        return $result->toArray($callback);
    }

    /**
     * Proceeds to include the given variable into the database environnement so that the executed queries, triggers or
     * stored procedures could have access to the variable. Useful for example to pass a user id to register for
     * automated log triggers.
     *
     * @param string $name
     * @param string $value
     */
    protected function addSessionVariable(string $name, string $value)
    {
        $this->database->addSessionVariable($name, $value);
    }

    private function prepareStatement(string $query, array $parameters = []): DatabaseStatement
    {
        // TODO: Sanitize input
        $statement = $this->database->query($query, $parameters); // TODO: Database exceptions should be runtime?
        //if (!is_null($this->sanitizeCallback)) {
        //    $statement->setSanitizeCallback($this->sanitizeCallback);
        //}
        return $statement;
    }
}
