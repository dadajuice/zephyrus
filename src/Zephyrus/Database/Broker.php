<?php namespace Zephyrus\Database;

use PDO;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Utilities\Pager;

abstract class Broker
{
    const SQL_FORMAT_DATE = "Y-m-d";
    const SQL_FORMAT_DATE_TIME = "Y-m-d H:i:s";
    const DEFAULT_FETCH = PDO::FETCH_BOTH;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Pager
     */
    private $pager = null;

    /**
     * @var int
     */
    private $fetchStyle = self::DEFAULT_FETCH;

    /**
     * Broker constructor called by children. Simply get the database reference
     * for further use.
     *
     * @param null|Database $database
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database;
        if (is_null($this->database)) {
            $this->database = Database::buildFromConfiguration();
        }
    }

    /**
     * @param int $count
     * @param int $limit
     * @param string $urlParameter
     * @return Pager
     */
    public function buildPager($count, $limit = Pager::PAGE_MAX_ENTITIES, $urlParameter = Pager::URL_PARAMETER)
    {
        $this->pager = new Pager($count, $limit, $urlParameter);
        return $this->pager;
    }

    /**
     * @return Pager
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * @param int $fetchStyle
     */
    protected function setFetchStyle(int $fetchStyle)
    {
        $this->fetchStyle = $fetchStyle;
    }

    /**
     * @return Database
     */
    protected function getDatabase()
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
     * Execute a SELECT query which should return a single data row. Best
     * suited for queries involving primary key in where. Will return null
     * if the query did not return any results. If more than one row is
     * returned, an exception is thrown.
     *
     * @param string $query
     * @param array $parameters
     * @param string $allowedTags
     * @throws DatabaseException
     * @return array | false
     */
    protected function selectSingle($query, $parameters = [], $allowedTags = "")
    {
        $statement = $this->query($query, $parameters);
        $statement->setAllowedHtmlTags($allowedTags);
        return $statement->next($this->fetchStyle);
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will
     * return an empty array if the query did not return any results.
     *
     * @param string $query
     * @param array $parameters
     * @param string $allowedTags
     * @return array
     */
    protected function select($query, $parameters = [], $allowedTags = "")
    {
        if (!is_null($this->pager)) {
            $query .= $this->pager->getSqlLimit();
        }
        $statement = $this->query($query, $parameters);
        $statement->setAllowedHtmlTags($allowedTags);
        $results = [];
        while ($row = $statement->next($this->fetchStyle)) {
            $results[] = $row;
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
     * @throws DatabaseException
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

    /**
     * Execute any type of query and simply return the DatabaseStatement
     * object ready to be fetched.
     *
     * @param string $query
     * @param array $parameters
     * @throws DatabaseException
     * @return DatabaseStatement
     */
    protected function query($query, $parameters = [])
    {
        return $this->database->query($query, $parameters);
    }
}
