<?php namespace Zephyrus\Database\Components;

use Zephyrus\Utilities\Components\PagerParser;

class QueryFilter
{
    private FilterParser $filterParser;
    private SortParser $sortParser;
    private PagerParser $pagerParser;
    private array $queryParameters = [];

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

    /**
     * @return FilterParser
     */
    public function getFilterParser(): FilterParser
    {
        return $this->filterParser;
    }

    /**
     * @return SortParser
     */
    public function getSortParser(): SortParser
    {
        return $this->sortParser;
    }

    /**
     * @return PagerParser
     */
    public function getPagerParser(): PagerParser
    {
        return $this->pagerParser;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getSearch(): string
    {
        return $this->filterParser->getSearch();
    }

    public function getFilters(): array
    {
        return $this->filterParser->getFilters();
    }

    /**
     * Proceeds to inject the SQL filtering (WHERE clause) to the given query. Will ignore if no filter has been
     * specified in the request.
     *
     * @param string $rawQuery
     * @return string
     */
    public function filter(string $rawQuery): string
    {
        if (!$this->isFilterRequested()) {
            return $rawQuery;
        }
        $this->filterParser->parse();
        return $this->injectWhereClause($rawQuery);
    }

    /**
     * Proceeds to inject SQL sorting (ORDER BY clause) to the given query. Will ignore if no sort has been specified in
     * the request and no default sort is given.
     *
     * @param string $rawQuery
     * @return string
     */
    public function sort(string $rawQuery): string
    {
        if (!$this->isSortRequested()
            && !$this->sortParser->hasDefaultSort()) {
            return $rawQuery;
        }
        return $this->injectOrderByClause($rawQuery);
    }

    /**
     * Proceeds to inject SQL pagination (LIMIT clause) to the given query. Will ignore if no page has been specified in
     * the request and pagination is not forced.
     *
     * @param string $rawQuery
     * @param bool $forcePaginate
     * @return string
     */
    public function paginate(string $rawQuery, bool $forcePaginate = true): string
    {
        if (!$this->isPaginationRequested() && !$forcePaginate) {
            return $rawQuery;
        }
        $limitClause = $this->pagerParser->parse()->buildLimitClause();
        return rtrim($rawQuery) . ' ' . $limitClause->getSql();
    }

    private function injectOrderByClause(string $query): string
    {
        $orderByClause = $this->sortParser->parse();
        $orderBy = $orderByClause->getSql();
        if (empty($orderBy)) {
            return $query;
        }

        $lastFromByOccurrence = strripos($query, "from");
        $lastWhereByOccurrence = strripos($query, "where", $lastFromByOccurrence);
        $lastLimitOccurrence = strripos($query, "limit", $lastFromByOccurrence);
        $insertionPosition = strlen($query);
        if ($lastLimitOccurrence !== false && $lastLimitOccurrence > $lastWhereByOccurrence) {
            $lastParenthesisOccurrences = strripos($query, ")");
            if ($lastParenthesisOccurrences === false || ($lastLimitOccurrence > $lastParenthesisOccurrences)) {
                $insertionPosition = $lastLimitOccurrence - 1;
            }
        }
        $begin = substr($query, 0, $insertionPosition);
        $end = substr($query, $insertionPosition);
        return rtrim($begin) . ' ' . $orderBy . $end;
    }

    private function injectWhereClause(string $query): string
    {
        $whereClause = $this->filterParser->getSqlClause();
        $where = $whereClause->getSql();
        if (empty($where)) {
            return $query;
        }

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
        $clause = (($lastWhereByOccurrence !== false) ? " AND " : " WHERE ");

        $this->queryParameters += $whereClause->getQueryParameters();
        $where = str_replace('WHERE ', '', $where); // Remove WHERE to build manually ...
        return rtrim($begin) . $clause . $where . $end;
    }
}
