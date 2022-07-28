<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\LimitClause;
use Zephyrus\Database\QueryBuilder\OrderByClause;
use Zephyrus\Database\QueryBuilder\WhereClause;

class QueryFilter
{
    private FilterParser $filterParser;
    private SortParser $sortParser;
    private PagerParser $pagerParser;

    private LimitClause $limitClause;
    private WhereClause $whereClause;
    private OrderByClause $orderByClause;

    public function __construct()
    {
        $this->filterParser = new FilterParser();
        $this->pagerParser = new PagerParser();
        $this->sortParser = new SortParser();
    }

    public function isFilterRequested(): bool
    {
        return $this->filterParser->hasRequested();
    }

    public function isSortRequested(): bool
    {
        return $this->sortParser->hasRequested();
    }

    public function isPaginationRequested(): bool
    {
        return $this->pagerParser->hasRequested();
    }

    public function setAllowedSortColumns(array $allowedSorts)
    {
        $this->sortParser->setAllowedColumns($allowedSorts);
    }

    public function setAllowedFilterColumns(array $allowedSorts)
    {
        $this->filterParser->setAllowedColumns($allowedSorts);
    }

    public function filter(string $rawQuery): string
    {
        if (!$this->isFilterRequested()) {
            return $rawQuery;
        }
        $this->whereClause = $this->filterParser->parse();
        return $this->injectWhereClause($rawQuery);
    }

    public function sort(string $rawQuery): string
    {
        if (!$this->isSortRequested()) { // TODO: OR DEFAULT SORT
            return $rawQuery;
        }
        $this->orderByClause = $this->sortParser->parse();
        return $this->injectOrderByClause($rawQuery);
    }

    public function paginate(string $rawQuery): string
    {
        if (!$this->isPaginationRequested() && false) { // TODO: OR NEEDED EXPLICITLY
            return $rawQuery;
        }
        $this->limitClause = $this->pagerParser->parse();
        return rtrim($rawQuery) . ' ' . $this->limitClause->getSql();
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
        return (!empty($orderBy)) ? rtrim($begin) . ' ' . $orderBy . $end : $query;
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
        //$clause = (($lastWhereByOccurrence !== false) ? " AND " : " WHERE "); // TODO: Treat as AND or OR with config ?
        $where = $this->whereClause->getSql();
        return (!empty($where)) ? rtrim($begin) . ' ' . $where . $end : $query;
    }
}
