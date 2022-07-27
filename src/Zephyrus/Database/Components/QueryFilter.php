<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\LimitClause;
use Zephyrus\Database\QueryBuilder\OrderByClause;
use Zephyrus\Database\QueryBuilder\WhereClause;

class QueryFilter
{
    private LimitClause $limitClause;
    private WhereClause $whereClause;
    private OrderByClause $orderByClause;

    public function __construct()
    {
        $this->initializeFilters();
        $this->initializeSorting();
        $this->initializePagination();
    }

    public function hasFilter(): bool
    {
        return !empty($this->whereClause->getSql());
    }

    public function hasSort(): bool
    {
        return !empty($this->orderByClause->getSql());
    }

    public function hasPagination(): bool
    {
        return !empty($this->limitClause->getSql());
    }

    public function filter(string $rawQuery): string
    {
        if (!$this->hasFilter()) {
            return $rawQuery;
        }
        return $this->injectWhereClause($rawQuery);
    }

    public function sort(string $rawQuery): string
    {
        if (!$this->hasSort()) {
            return $rawQuery;
        }
        return $this->injectOrderByClause($rawQuery);
    }

    public function paginate(string $rawQuery): string
    {
        if (!$this->hasPagination()) {
            return $rawQuery;
        }
        return $rawQuery . $this->limitClause->getSql();
    }

    private function injectOrderByClause(string $query): string
    {
        $lastFromByOccurrence = strripos($query, "from");
        $lastWhereByOccurrence = strripos($query, "where", $lastFromByOccurrence);
        $lastLimitOccurrence = strripos($query, "limit", $lastFromByOccurrence);
        $insertionPosition = strlen($query);
        if ($lastLimitOccurrence !== false && $lastLimitOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastLimitOccurrence - 1;
        }
        $begin = substr($query, 0, $insertionPosition);
        $end = substr($query, $insertionPosition);
        $orderBy = $this->orderByClause->getSql();
        return (!empty($orderBy)) ? $begin . $orderBy . $end : $query;
    }

    private function injectWhereClause(string $query): string
    {
        $lastFromByOccurrence = strripos($query, "from");
        $lastWhereByOccurrence = strripos($query, "where", $lastFromByOccurrence);
        $lastGroupByOccurrence = strripos($query, "group by", $lastFromByOccurrence);
        $lastHavingByOccurrence = strripos($query, "having", $lastFromByOccurrence);
        $insertionPosition = strlen($query);
        if ($lastGroupByOccurrence !== false && $lastGroupByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastGroupByOccurrence - 1;
        } elseif ($lastHavingByOccurrence !== false && $lastHavingByOccurrence > $lastWhereByOccurrence) {
            // Having without a group by clause case (valid as standard SQL)
            $insertionPosition = $lastHavingByOccurrence - 1;
        }
        $begin = substr($query, 0, $insertionPosition);
        $end = substr($query, $insertionPosition);
        $clause = (($lastWhereByOccurrence !== false) ? " AND " : " WHERE "); // TODO: Treat as AND or OR with config ?
        $where = $this->whereClause->getSql();
        return (!empty($where)) ? $begin . $clause . '(' . $where . ')' . $end : $query;
    }

    /**
     * Prepares the WHERE clause based on the request parameters following the filter structure.
     */
    private function initializeFilters()
    {
        $filter = new FilterParser();
        $this->whereClause = $filter->parse();
    }

    /**
     * Prepares the LIMIT clause based on the request parameters following the pager structure.
     */
    private function initializePagination()
    {
        $pager = new PagerParser();
        $this->limitClause = $pager->parse();
    }

    /**
     * Prepares the ORDER BY clause based on the request parameters following the sort structure.
     */
    private function initializeSorting()
    {
        $sort = new SortParser();
        $this->orderByClause = $sort->parse();
    }
}
