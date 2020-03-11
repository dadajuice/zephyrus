<?php namespace Zephyrus\Database;

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

    /**
     * @return null | Filter
     */
    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

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
     * Creates the filter according to the request.
     *
     * @param string $defaultSort
     * @param string $defaultOrder
     */
    public function applyFilter(string $defaultSort = "", string $defaultOrder = "")
    {
        $this->filter = new Filter(RequestFactory::read(), $defaultSort, $defaultOrder);
    }

    public function removeFilter()
    {
        $this->filter = null;
    }

    public function filterQuery(string &$query, bool $ignoreOrder = false)
    {
        if (is_null($this->filter)) {
            return;
        }
        if ($this->filter->hasSearch()) {
            $query = $this->buildSearch($query);
        }
        if (!$ignoreOrder) {
            $query .= $this->buildOrderBy();
        }
    }

    private function buildSearch(string $query)
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
        return $begin . $this->buildWhere($query) . $end;
    }

    private function buildWhere(string $query): string
    {
        $lastWhereByOccurrence = strripos($query, "where");
        return (($lastWhereByOccurrence !== false) ? " AND " : " WHERE ") . '(' . $this->search() . ')';
    }

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

    private function search()
    {
        $clause = "";
        foreach ($this->searchableFields as $field) {
            if (!empty($clause)) {
                $clause .= ' OR ';
            }
            $clause .= $this->getAdapter()->searchPattern($field, $this->getFilter()->getSearch());
        }
        return $clause;
    }
}
