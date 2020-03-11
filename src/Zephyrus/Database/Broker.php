<?php namespace Zephyrus\Database;

use stdClass;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Utilities\Pager;

abstract class Broker
{
    const SQL_FORMAT_DATE = "Y-m-d";
    const SQL_FORMAT_DATE_TIME = "Y-m-d H:i:s";

    /**
     * @var Database
     */
    private $database;

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
            $this->database = Database::buildFromConfiguration();
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addSessionVariable(string $name, string $value)
    {
        $this->database->getAdapter()->addSessionVariable($name, $value);
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
     * Shorthand method to begin a transaction.
     */
    protected function beginTransaction()
    {
        $this->database->beginTransaction();
    }

    /**
     * Shorthand method to commit a started transaction.
     *
     * @throws DatabaseException
     */
    protected function commit()
    {
        $this->database->commit();
    }

    /**
     * Shorthand method to rollback a started transaction.
     *
     * @throws DatabaseException
     */
    protected function rollback()
    {
        $this->database->rollback();
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
     * @return stdClass|null
     */
    protected function selectSingle(string $query, array $parameters = [], string $allowedTags = ""): ?stdClass
    {
        $query = $this->filterQuery($query);
        $statement = $this->query($query, $parameters);
        $statement->setAllowedHtmlTags($allowedTags);
        return $statement->next();
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will
     * return an empty array if the query did not return any results.
     *
     * @param string $query
     * @param array $parameters
     * @param string $allowedTags
     * @return \stdClass[]
     */
    protected function select(string $query, array $parameters = [], string $allowedTags = ""): array
    {
        if (!is_null($this->pager)) {
            $query .= $this->pager->getSqlLimitClause($this->database->getAdapter());
        }
        $query = $this->filterQuery($query);
        $statement = $this->query($query, $parameters);
        $statement->setAllowedHtmlTags($allowedTags);
        $results = [];
        while ($row = $statement->next()) {
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
     * @return DatabaseStatement
     */
    protected function query(string $query, array $parameters = [])
    {
        return $this->database->query($query, $parameters);
    }

    public function list(string $defaultSort = "", string $defaultOrder = "asc", int $pagerLimit = Pager::DEFAULT_PAGE_MAX_ENTITIES): stdClass
    {
        if (!($broker instanceof Listable)) {
            throw new \RuntimeException("Provided broker must implements the Listable instance");
        }

        $totalCount = $broker->getDatabase()->getAdapter()->countAll('');
        $broker->applyFilter($defaultSort, $defaultOrder);
        $rows = $broker->findAll();
        $count = $broker->count();

        if ($pagerLimit > 0) {
            $broker->buildPager($count, $pagerLimit);
        }

        return (object) [
            'results' => (object) [
                'rows' => $rows,
                'count' => $count,
                'totalCount' => $totalCount,
            ],
            'pager' => (object) [
                'maxPage' => (!is_null($broker->getPager())) ? $broker->getPager()->getMaxPage() : 0,
                'currentPage' => (!is_null($broker->getPager())) ? $broker->getPager()->getCurrentPage() : 0,
                'maxEntitiesPerPage' => (!is_null($broker->getPager())) ? $broker->getPager()->getMaxEntitiesPerPage() : 0
            ],
            'filter' => (object) [
                'search' => $broker->getFilter()->getSearch(),
                'sort' => $broker->getFilter()->getSort(),
                'order' => $broker->getFilter()->getOrder()
            ]
        ];
    }
}
