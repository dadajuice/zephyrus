<?php namespace Zephyrus\Utilities\Listing;

use Zephyrus\Database\DatabaseBroker;

abstract class ListModel
{
    private const COUNT_VAR = "_zf_count";

    public const FILTERS_PARAMETER = 'filters[]';
    public const SEARCH_PARAMETER = 'search';
    public const SORTS_PARAMETER = 'sorts[]';
    public const PAGE_PARAMETER = 'page';
    public const LIMIT_PARAMETER = 'limit';

    use ListQuery;

    private DatabaseBroker $databaseBroker;
    private string $listIdentifier;
    private ListFunnel $funnel;
    private ListPagination $pagination;
    private ListSort $sort;

    /**
     * @var FilterView[]
     */
    private array $filterViews = [];

    abstract protected function configureOptions(): void;

    abstract protected function configureFilters(): void;

    abstract protected function findRows(): array;

    public function __construct(array $configurations)
    {
        $this->funnel = new ListFunnel(
            $configurations[self::FILTERS_PARAMETER] ?? [],
            $configurations[self::SEARCH_PARAMETER] ?? null
        );
        $this->pagination = new ListPagination(
            $configurations[self::PAGE_PARAMETER] ?? null,
            $configurations[self::LIMIT_PARAMETER] ?? null
        );
        $this->sort = new ListSort($configurations[self::SORTS_PARAMETER] ?? []);
        $this->setDatabaseBroker(new class() extends DatabaseBroker {
            public function filteredSelect(string $query, array $parameters = [], ?callable $callback = null): array
            {
                return $this->select($query, $parameters, $callback);
            }
        });
        $this->configureFilters();
        $this->configureOptions();
    }

    public function addFilterView(FilterView $filterView): void
    {
        $this->filterViews[] = $filterView;
    }

    public function inflate(): ListView
    {
        $results = $this->findRows();
        $count = $results ? $results[0]->_zf_count : 0;
        $list = new ListView($results);
        $list->setModel($this);
        $list->setCount($count);
        $list->setFilterViews($this->filterViews);
        return $list;
    }

    public function inflateGroupedList(string $groupColumn, ?callable $formatCallback = null): ListGroupView
    {
        $results = $this->findRows();
        $count = $results ? $results[0]->_zf_count : 0;
        $list = new ListGroupView($results, $groupColumn);
        $list->setModel($this);
        $list->setCount($count);
        $list->setFilterViews($this->filterViews);
        if (!is_null($formatCallback)) {
            $list->setHeaderFormatting($formatCallback);
        }
        return $list;
    }

    public function getFunnel(): ListFunnel
    {
        return $this->funnel;
    }

    public function getPagination(): ListPagination
    {
        return $this->pagination;
    }

    public function getSort(): ListSort
    {
        return $this->sort;
    }

    /**
     * Overrides the default baseline database broker instance to be used. Must have the filteredSelect method defined.
     *
     * @param DatabaseBroker $databaseBroker
     */
    protected function setDatabaseBroker(DatabaseBroker $databaseBroker): void
    {
        $this->databaseBroker = $databaseBroker;
    }
}
