<?php namespace Zephyrus\Database;

use stdClass;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseStatement;
use Zephyrus\Database\Core\Filterable;
use Zephyrus\Database\Core\Pageable;
use Zephyrus\Exceptions\DatabaseException;

abstract class DatabaseBroker
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var string
     */
    private $allowedHtmlTags = "";

    use Pageable;
    use Filterable { filterQuery as private; }

    /**
     * Broker constructor called by children. Simply get the database reference
     * for further use. Pageable.
     *
     * @param null|Database $database
     * @throws DatabaseException
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database;
        if (is_null($this->database)) {
            $this->database = DatabaseFactory::buildFromConfigurations();
        }
    }

    /**
     * @return Database
     */
    protected function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * @param Database $database
     */
    protected function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function addSessionVariable(string $name, string $value)
    {
        $this->query($this->database->getAdapter()->getAddEnvironmentVariableClause($name, $value));
    }

    /**
     * <p><a>.
     *
     * @param string $allowedTags
     */
    protected function setAllowedHtmlTags(string $allowedTags)
    {
        $this->allowedHtmlTags = $allowedTags;
    }

    protected function getAllowedHtmlTags(): string
    {
        return $this->allowedHtmlTags;
    }

    protected function removeAllowedHtmlTags()
    {
        $this->allowedHtmlTags = "";
    }

    /**
     * Executes any type of query and simply returns the DatabaseStatement
     * object ready to be fetched. Will throw a DatabaseException is the query
     * fails to execute.
     *
     * @param string $query
     * @param array $parameters
     * @throws DatabaseException
     * @return DatabaseStatement
     */
    protected function query(string $query, array $parameters = []): DatabaseStatement
    {
        return $this->database->query($query, $parameters);
    }

    /**
     * Executes a SELECT query which should return a single data row. Best
     * suited for queries involving primary key in where. Will return null
     * if the query did not fetch any result.
     *
     * @param string $query
     * @param array $parameters
     * @param string $allowedTags
     * @return stdClass|null
     */
    protected function selectSingle(string $query, array $parameters = []): ?stdClass
    {
        $query = $this->filterQuery($query);
        $statement = $this->query($query, $parameters);
        $statement->setAllowedHtmlTags($this->allowedHtmlTags);
        return $statement->next();
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will
     * return an empty array if the query did not return any results.
     *
     * @param string $query
     * @param array $parameters
     * @param callable $callback
     * @return \stdClass[]
     */
    protected function select(string $query, array $parameters = [], ?callable $callback = null): array
    {
        if (!is_null($this->pager)) {
            $query .= $this->pager->getSqlLimitClause($this->database->getAdapter());
        }
        $query = $this->filterQuery($query);
        $statement = $this->query($query, $parameters);
        $statement->setAllowedHtmlTags($this->allowedHtmlTags);
        $results = [];
        while ($row = $statement->next()) {
            $results[] = (is_null($callback)) ? $row : $callback($row);
        }
        return $results;
    }

    /**
     * Execute a query which should be contain inside a transaction. The specified
     * callback method will optionally receive the Database instance if one argument
     * is defined. Will work with nested transactions if using the TransactionPDO
     * handler. Best suited method for INSERT, UPDATE and DELETE queries.
     *
     * @param callable $callback
     * @return mixed
     */
    protected function transaction(callable $callback)
    {
        try {
            $this->database->beginTransaction();
            $reflect = new \ReflectionFunction($callback);
            if ($reflect->getNumberOfParameters() == 1) {
                $result = $callback($this->database);
            } elseif ($reflect->getNumberOfParameters() == 0) {
                $result = $callback();
            } else {
                throw new \InvalidArgumentException("Specified callback must have 0 or 1 argument");
            }
            $this->database->commit();
            return $result;
        } catch (\Exception $e) {
            $this->database->rollback();

            throw new DatabaseException($e->getMessage());
        }
    }
}
