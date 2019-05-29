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
     * @return null | Filter
     */
    public function getFilter(): ?Filter
    {
        return $this->filter;
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

    public function filterQuery(string& $query, array& $parameters = [], bool $ignoreOrder = false)
    {
        if (!is_null($this->filter)) {
            $search = $this->filter->getSearch();
            if (!is_null($search)) {
                $search = "%$search%";
                $query = $this->buildSearch($query);
            }
            if (!$ignoreOrder) {
                $query .= $this->buildOrderBy();
            }
            $parameters = array_merge($parameters, ['search' => $search]);
        }
    }

    private function buildSearch(string $query)
    {
        $lastWhereByOccurrence = strripos($query, "where");
        $lastGroupByOccurrence = strripos($query, "group by");
        $lastHavingByOccurrence = strripos($query, "having");
        $insertionPosition = strlen($query);
        if ($lastGroupByOccurrence !== false && $lastGroupByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastGroupByOccurrence;
        } elseif ($lastHavingByOccurrence !== false && $lastHavingByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastHavingByOccurrence;
        }
        $begin = substr($query, 0, $insertionPosition);
        $end = substr($query, $insertionPosition);
        return $begin . $this->buildWhere($query) . $end;
    }

    private function buildWhere(string $query): string
    {
        $lastWhereByOccurrence = strripos($query, "where");
        return (($lastWhereByOccurrence !== false) ? " AND " : " WHERE ") . $this->search();
    }

    private function buildOrderBy(): string
    {
        $sort = $this->filter->getSort();
        if (!empty($sort)) {
            $order = $this->filter->getOrder();
            $convertedSorts = $this->sort($order);
            $orderBy = (array_key_exists($sort, $convertedSorts)) ? $convertedSorts[$sort] : $sort;
            return " ORDER BY $orderBy $order";
        }
        return "";
    }
}