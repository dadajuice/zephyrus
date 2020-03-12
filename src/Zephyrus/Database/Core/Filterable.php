<?php namespace Zephyrus\Database\Core;

use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Filter;

trait Filterable
{
    /**
     * @var Filter
     */
    private $filter = null;

    /**
     * @var array
     */
    private $searchableFields = [];

    /**
     * @var array
     */
    private $sortableFields = [];

    // ['firstname', 'lastname']
    protected function setSearchableFields(array $fields)
    {
        $this->searchableFields = $fields;
    }

    // ['nom' => 'firstname']
    protected function setSortableFields(array $fields)
    {
        $this->sortableFields = $fields;
    }

    /**
     * Applies a Filter instance based on the HTTP Request for the current
     * broker. Any subsequent select queries will automatically include the
     * filter (sort, order and search).
     *
     * @param string $defaultSort
     * @param string $defaultOrder
     */
    public function applyFilter(string $defaultSort = "", string $defaultOrder = "")
    {
        $this->filter = new Filter(RequestFactory::read(), $defaultSort, $defaultOrder);
    }

    /**
     * Removes the applied filter meaning that any subsequent filteredQuery
     * wont use the filter.
     */
    public function removeFilter()
    {
        $this->filter = null;
    }

    /**
     * Adds the correct SQL clause to the given query for search terms and sort
     * order if any filter is given.
     *
     * @param string $query
     * @return string
     */
    public function filterQuery(string $query): string
    {
        if (is_null($this->filter)) {
            return $query;
        }
        if ($this->filter->hasSearch()) {
            $query = $this->buildSearchWhere($query);
        }
        if ($this->filter->hasSort()) {
            $query .= $this->buildOrderBy();
        }
        return $query;
    }

    /**
     * @return null | Filter
     */
    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

    /**
     * Includes the WHERE clause properly placed inside the given query.
     *
     * @param string $query
     * @return string
     */
    private function buildSearchWhere(string $query): string
    {
        $lastWhereByOccurrence = strripos($query, "where");
        $lastGroupByOccurrence = strripos($query, "group by");
        $lastHavingByOccurrence = strripos($query, "having");
        $insertionPosition = strlen($query);
        if ($lastGroupByOccurrence !== false && $lastGroupByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastGroupByOccurrence - 1;
        } elseif ($lastHavingByOccurrence !== false && $lastHavingByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastHavingByOccurrence - 1;
        }
        $begin = substr($query, 0, $insertionPosition);
        $end = substr($query, $insertionPosition);
        $clause = (($lastWhereByOccurrence !== false) ? " AND " : " WHERE ");
        return $begin . $clause . '(' . $this->buildSearch() . ')' . $end;
    }

    /**
     * Builds the ORDER BY sql clause according to the filter setting. If no
     * sort correspondences exists, it will directly use the sort url argument
     * as order field.
     *
     * @return string
     */
    private function buildOrderBy(): string
    {
        $sort = $this->filter->getSort();
        if (!empty($sort)) {
            $order = $this->filter->getOrder();
            $clause = $this->sortableFields[$sort] ?? $sort;
            return " ORDER BY $clause $order";
        }
        return "";
    }

    /**
     * Builds the search clause (e.g firstname LIKE %test% OR lastname
     * LIKE %test%). Uses a defined searchPattern method in the database
     * adapter.
     *
     * @return string
     */
    private function buildSearch(): string
    {
        $clause = "";
        foreach ($this->searchableFields as $field) {
            if (!empty($clause)) {
                $clause .= ' OR ';
            }
            $clause .= $this->getDatabase()->getAdapter()->getSearchFieldClause($field, $this->getFilter()->getSearch());
        }
        return $clause;
    }
}
