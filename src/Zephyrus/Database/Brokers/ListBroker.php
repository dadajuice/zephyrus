<?php namespace Zephyrus\Database\Brokers;

use stdClass;
use Zephyrus\Database\Components\QueryFilter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Components\ListFilter;
use Zephyrus\Utilities\Components\ListGroupView;
use Zephyrus\Utilities\Components\ListView;
use Zephyrus\Utilities\Components\Pagination;

abstract class ListBroker extends DatabaseBroker
{
    private QueryFilter $queryFilter;
    private ListFilter $filter;
    private array $columnAlias = []; // ['price' => 'amount']
    private array $allowedFilterColumns = []; // ['name', 'price', 'brand']
    private array $allowedSortColumns = []; // ['name', 'price', 'brand']
    private array $defaultSorts = []; // ['name' => 'asc', 'price' => 'desc']
    private array $searchableColumns = []; // ['name', 'brand'] (no alias)
    private bool $ascNullLast = true;
    private bool $descNullLast = false;
    private int $defaultPagerLimit = Pagination::DEFAULT_LIMIT;
    private int $maxPagerLimit = Pagination::DEFAULT_LIMIT;

    /**
     * Force the configuration of the list broker with the allowed columns, default sorts and alias if applicable.
     */
    abstract protected function configure();

    /**
     * Returns the filtered rows.
     *
     * @return array
     */
    abstract public function findRows(): array;

    /**
     * Retrieves from the database the total count for the findAllRows() corresponding query.
     *
     * @return stdClass
     */
    abstract public function count(): stdClass;

    public function __construct(?Database $database = null)
    {
        parent::__construct($database);
        $this->configure();

        $this->filter = new ListFilter(RequestFactory::read()->getFilterConfiguration());
        $this->filter->getSort()->setDefaultSorts($this->defaultSorts);
        $this->filter->getFunnel()->setAllowedFields($this->allowedFilterColumns);
        $this->filter->getSort()->setAllowedFields($this->allowedSortColumns);
        $this->filter->getPagination()->setDefaultLimit($this->defaultPagerLimit, $this->maxPagerLimit);

        $this->queryFilter = new QueryFilter($this->filter);
        $this->queryFilter->getFilterParser()->setAliasColumns($this->columnAlias);
        $this->queryFilter->getFilterParser()->setSearchableColumns($this->searchableColumns);
        $this->queryFilter->getSortParser()->setAliasColumns($this->columnAlias);
        $this->queryFilter->getSortParser()->setAscNullLast($this->ascNullLast);
        $this->queryFilter->getSortParser()->setDescNullLast($this->descNullLast);
    }

    public function inflate(): ListView
    {
        $list = new ListView($this->findRows());
        $list->setFilter($this->filter);
        $count = $this->count();
        $list->setCount($count->current, $count->total);
        return $list;
    }

    public function inflateGroupedList(string $groupColumn, ?callable $formatCallback = null): ListGroupView
    {
        $list = new ListGroupView($this->findRows());
        $list->setFilter($this->filter);
        $count = $this->count();
        $list->setCount($count->current, $count->total);
        $list->setHeaderColumn($groupColumn);
        if (!is_null($formatCallback)) {
            $list->setHeaderFormatting($formatCallback);
        }
        return $list;
    }

    /**
     * Allows to manually apply the pagination (LIMIT) to the given SQL query.
     *
     * @param string $query
     * @return string
     */
    protected function paginate(string $query): string
    {
        return $this->queryFilter->paginate($query);
    }

    /**
     * Allows to manually apply the sort (ORDER BY) to the given SQL query.
     *
     * @param string $query
     * @return string
     */
    protected function sort(string $query): string
    {
        return $this->queryFilter->sort($query);
    }

    /**
     * Allows to manually apply the filter (WHERE) to the given SQL query.
     *
     * @param string $query
     * @return string
     */
    protected function filter(string $query): string
    {
        return $this->queryFilter->filter($query);
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
        return self::select($query, array_merge($parameters, $this->queryFilter->getQueryParameters()), $callback);
    }

    /**
     * Executes a given "SELECT count(*) as n" query to calculate the number of rows related to a list. Will return an
     * object with two properties : current and total. The given query MUST have the "as n" column alias to work.
     *
     * @param string $query
     * @param array $parameters
     * @return stdClass
     */
    protected function countQuery(string $query, array $parameters = []): stdClass
    {
        $total = self::selectSingle($query, $parameters)->n;
        $query = $this->queryFilter->filter($query);
        $current = self::selectSingle($query, array_merge($parameters, $this->queryFilter->getQueryParameters()))->n;
        return (object) [
            'current' => $current,
            'total' => $total
        ];
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
