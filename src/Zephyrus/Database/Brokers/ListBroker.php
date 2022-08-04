<?php namespace Zephyrus\Database\Brokers;

use Zephyrus\Database\Components\PagerParser;
use Zephyrus\Database\Components\QueryFilter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Utilities\Components\ListView;

abstract class ListBroker extends DatabaseBroker
{
    private QueryFilter $queryFilter;
    private array $columnAlias = []; // ['price' => 'amount']
    private array $allowedFilterColumns = []; // ['name', 'price', 'brand']
    private array $allowedSortColumns = []; // ['name', 'price', 'brand']
    private array $defaultSorts = []; // ['name' => 'asc', 'price' => 'desc']
    private array $searchableColumns = []; // ['name', 'brand'] (no alias)
    private bool $ascNullLast = true;
    private bool $descNullLast = false;
    private int $defaultPagerLimit = PagerParser::DEFAULT_LIMIT;
    private int $maxPagerLimit = PagerParser::DEFAULT_LIMIT;

    /**
     * Force the configuration of the list broker with the allowed columns, default sorts and alias if applicable.
     */
    abstract protected function configure();

    /**
     * Returns the filtered rows.
     *
     * @return array
     */
    abstract public function findAllRows(): array;

    /**
     * Retrieves from the database the total count for the findAllRows() corresponding query.
     *
     * @return \stdClass
     */
    abstract public function count(): \stdClass;

    public function __construct(?Database $database = null)
    {
        parent::__construct($database);
        $this->configure();
        $this->queryFilter = new QueryFilter();
        $this->queryFilter->getFilterParser()->setAllowedColumns($this->allowedFilterColumns);
        $this->queryFilter->getFilterParser()->setAliasColumns($this->columnAlias);
        $this->queryFilter->getFilterParser()->setSearchableColumns($this->searchableColumns);
        $this->queryFilter->getSortParser()->setAllowedColumns($this->allowedSortColumns);
        $this->queryFilter->getSortParser()->setAliasColumns($this->columnAlias);
        $this->queryFilter->getSortParser()->setDefaultSorts($this->defaultSorts);
        $this->queryFilter->getSortParser()->setAscNullLast($this->ascNullLast);
        $this->queryFilter->getSortParser()->setDescNullLast($this->descNullLast);
        $this->queryFilter->getPagerParser()->setDefaultLimit($this->defaultPagerLimit);
        $this->queryFilter->getPagerParser()->setMaxLimitAllowed($this->maxPagerLimit);
    }

    public function inflate(): ListView
    {
        $rows = $this->findAllRows();
        $list = new ListView($rows);
        $list->setQueryFilter($this->queryFilter);
        $count = $this->count();
        $list->setCount($count->current, $count->total);
        return $list;
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will filter the query according to the
     * current filter loaded into the broker class. Returns null if the query did not fetch any result.
     *
     * @param string $query
     * @param array $parameters
     * @param callable|null $callback
     * @return array
     */
    protected function filteredSelect(string $query, array $parameters = [], ?callable $callback = null): array
    {
        $query = $this->queryFilter->filter($query);
        $query = $this->queryFilter->sort($query);
        $query = $this->queryFilter->paginate($query);
        return self::select($query, $parameters + $this->queryFilter->getQueryParameters(), $callback);
    }

    /**
     * Ignore sort and pagination for count queries.
     *
     * @param string $query
     * @param array $parameters
     * @return \stdClass
     */
    protected function baseCount(string $query, array $parameters = []): \stdClass
    {
        $query = $this->queryFilter->filter($query);
        return self::selectSingle($query, $parameters + $this->queryFilter->getQueryParameters());
    }

    /**
     * Defines the column alias to use for the various column identifications (sorting and filtering). Feature allows
     * to hide the real database column linked with the sort from the user. E.g. allowing the sort on the price, but
     * the real column name in the database is 'amount'.
     *
     * @param array $columnAlias
     */
    final protected function setAliasColumns(array $columnAlias)
    {
        $this->columnAlias = $columnAlias;
    }

    /**
     * Defines the allowed column's names that can be used for sorting. Also works with alias.
     *
     * @param array $columnNames
     */
    final protected function setSortAllowedColumns(array $columnNames)
    {
        $this->allowedSortColumns = $columnNames;
    }

    /**
     * Defines the allowed column's names that can be used for filtering. Also works with alias.
     *
     * @param array $columnNames
     */
    final protected function setFilterAllowedColumns(array $columnNames)
    {
        $this->allowedFilterColumns = $columnNames;
    }

    /**
     * Defines the default sorts to use if none is specified by the request.
     *
     * @param array $sorts
     */
    final protected function setSortDefaults(array $sorts)
    {
        $this->defaultSorts = $sorts;
    }

    /**
     * Defines the columns used when a search request is performed on the list.
     *
     * @param array $columns
     */
    final protected function setSearchableColumns(array $columns)
    {
        $this->searchableColumns = $columns;
    }

    /**
     * Defines the default number of rows per page for the query results. Default is 50.
     *
     * @param int $limit
     */
    final protected function setPagerDefaultLimit(int $limit)
    {
        $this->defaultPagerLimit = $limit;
    }

    /**
     * Defines the maximum number of rows per page. Used when the final user can choose how many rows is displayed in
     * the list. In such case, it's possible to apply a maximum for security and performance reasons.
     *
     * @param int $limit
     */
    final protected function setPagerMaxLimit(int $limit)
    {
        $this->maxPagerLimit = $limit;
    }

    final protected function setSortAscNullLast(bool $nullLast)
    {
        $this->ascNullLast = $nullLast;
    }

    final protected function setSortDescNullLast(bool $nullLast)
    {
        $this->descNullLast = $nullLast;
    }
}
