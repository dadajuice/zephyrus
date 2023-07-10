<?php namespace Zephyrus\Utilities\Listing;

use Zephyrus\Database\QueryBuilder\LimitClause;
use Zephyrus\Database\QueryBuilder\OrderByClause;

trait ListQuery
{
    private array $queryParameters = [];

    protected function select(string $query, array $parameters = [], ?callable $callback = null): array
    {
        $query = $this->filter($query);
        $query = $this->sort($query);
        $query = $this->paginate($query);
        $query = $this->count($query);
        $parameters = array_merge($parameters, $this->queryParameters);
        return $this->databaseBroker->select($query, $parameters, $callback);
    }

    /**
     * Proceeds to inject the SQL filtering (WHERE clause) to the given query. Will ignore if no filter has been
     * specified in the request.
     *
     * @param string $query
     * @return string
     */
    private function filter(string $query): string
    {
        if (empty($this->getFunnel()->getFilters())
            && is_null($this->getFunnel()->getSearch())) {
            return $query;
        }
        return $this->injectWhereClause($query);
    }

    private function sort(string $query): string
    {
        if (empty($this->getSort()->getSorts())) {
            return $query;
        }
        return $this->injectOrderByClause($query);
    }

    /**
     * Proceeds to inject SQL pagination (LIMIT clause) to the given query.
     *
     * @param string $query
     * @return string
     */
    private function paginate(string $query): string
    {
        $limitClause = $this->buildLimitClause();
        return rtrim($query) . ' ' . $limitClause->getSql();
    }

    private function count(string $query): string
    {
        $firstSelectOccurrence = stripos($query, "select");
        $begin = substr($query, 0, $firstSelectOccurrence + 6);
        $end = substr($query, $firstSelectOccurrence + 6);
        return $begin . " count(*) OVER() AS " . self::COUNT_VAR . "," . $end;
    }

    private function injectWhereClause(string $query): string
    {
        $sqlParser = new SqlFunnelParser($this->funnel);
        $whereClause = $sqlParser->buildSqlClause();
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
        return rtrim($begin) . $clause . (($clause == ' AND ') ? "(" . $where . ")" : $where) . $end;
    }

    private function injectOrderByClause(string $query): string
    {
        $orderByClause = $this->buildOrderByClause();
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

    /**
     * Parses the request parameters to build a corresponding ORDER BY clause. The parameters should be given following
     * the public constants:.
     *
     *     example.com?sorts[column] = asc|desc
     *
     * If no sorts are given, the configured default sorts will be used. The NULLs ordering is defined by the
     * setAscNullLast and setDescNullLast methods. The columnConversion array allows specifying correspondance between
     * request parameters and database column (if developers don't want to expose database column directly in UI links).
     *
     * @return OrderByClause
     */
    private function buildOrderByClause(): OrderByClause
    {
        $orderByClause = new OrderByClause();
        $sorts = $this->sort->getSorts();
        foreach ($sorts as $column => $order) {
            match ($order) {
                'desc' => $orderByClause->desc($column, !$this->sort->isDescNullLast()),
                'asc' => $orderByClause->asc($column, $this->sort->isAscNullLast()),
            };
        }
        return $orderByClause;
    }

    /**
     * Parses the request parameters to build a corresponding PagerModel instance. The parameters should be given
     * following the public constants:.
     *
     *     example.com?page=4&limit=96
     *
     * The limit parameter is optional, as the default value (50 per page) will be used if none given. It cannot go
     * beyond the configured max limit allowed for security reason (avoid a user to manually select 15000 rows per
     * page). Developers should indicate the maximum allowed when permitting user to change the row count. By default,
     * it is limited to 50 (same as the default rows per page).
     *
     * @return LimitClause
     */
    private function buildLimitClause(): LimitClause
    {
        $limitClause = new LimitClause();
        $limit = $this->pagination->getLimit();
        $offset = $limit * ($this->pagination->getCurrentPage() - 1);
        $limitClause->setLimit($limit);
        $limitClause->setOffset($offset);
        return $limitClause;
    }
}
