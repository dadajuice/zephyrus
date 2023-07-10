<?php namespace Zephyrus\Utilities\Listing;

use InvalidArgumentException;
use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Database\QueryBuilder\WhereCondition;

class SqlFunnelParser
{
    private ListFunnel $funnel;
    private WhereClause $whereClause;
    private string $aggregateOperator = WhereClause::OPERATOR_AND;

    public function __construct(ListFunnel $funnel)
    {
        $this->funnel = $funnel;
        $this->whereClause = new WhereClause();
    }

    public function setAggregateOperator(string $operator): void
    {
        if (!in_array($operator, WhereClause::SUPPORTED_OPERATORS)) {
            throw new InvalidArgumentException("Invalid operator supplied. Supported aggregate operators are [OR, AND].");
        }
        $this->aggregateOperator = $operator;
    }

    public function getSqlClause(): WhereClause
    {
        return $this->whereClause;
    }

    /**
     * Parses the request parameters to build a corresponding WHERE clause. The parameters should be given following the
     * public constants:.
     *
     *     example.com?filters[column:type]=content
     *     example.com?search=batman
     *
     * The aliasColumn array allows specifying correspondance between request parameters and database column (if
     * developers don't want to expose database column directly in UI links). If a specified column is not allowed it
     * will be ignored. If no column type is given, the "contains" default will be considered.
     *
     * @return WhereClause
     */
    public function buildSqlClause(): WhereClause
    {
        $filterConditions = $this->parseFilters();
        foreach ($filterConditions as $condition) {
            $this->whereClause->add($condition, $this->aggregateOperator);
        }
        if (!is_null($this->funnel->getSearch())) {
            $searchCondition = call_user_func_array([WhereCondition::class, "or"], $this->parseSearch());
            $this->whereClause->add($searchCondition, $this->aggregateOperator);
        }
        return $this->whereClause;
    }

    private function parseFilters(): array
    {
        $conditions = [];
        foreach ($this->funnel->getFilters() as $column => $filters) {
            foreach ($filters as $filter) {
                match ($filter->type) {
                    'contains' => $conditions[] = $this->parseContains($column, $filter->value),
                    'begins' => $conditions[] = $this->parseBegins($column, $filter->value),
                    'ends' => $conditions[] = $this->parseEnds($column, $filter->value),
                    'sensible_contains' => $conditions[] = $this->parseContains($column, $filter->value, false),
                    'sensible_begins' => $conditions[] = $this->parseBegins($column, $filter->value, false),
                    'sensible_ends' => $conditions[] = $this->parseEnds($column, $filter->value, false),
                    'equals' => $conditions[] = $this->parseEquals($column, $filter->value),
                    'between' => $conditions[] = $this->parseBetween($column, $filter->value),
                    'less' => $conditions[] = $this->parseLessThan($column, $filter->value),
                    'greater' => $conditions[] = $this->parseGreaterThan($column, $filter->value),
                    'less_equals' => $conditions[] = $this->parseLessEqualsThan($column, $filter->value),
                    'greater_equals' => $conditions[] = $this->parseGreaterEqualsThan($column, $filter->value),
                    default => null
                };
            }
        }
        return $conditions;
    }

    private function parseBetween(string $column, mixed $content): WhereCondition
    {
        $contents = explode("~", $content);
        return WhereCondition::between($column, $contents[0], $contents[1]);
    }

    private function parseContains(string $column, mixed $content, bool $caseInsensitive = true): WhereCondition
    {
        return WhereCondition::like($column, "%$content%", $caseInsensitive);
    }

    private function parseBegins(string $column, mixed $content, bool $caseInsensitive = true): WhereCondition
    {
        return WhereCondition::like($column, "$content%", $caseInsensitive);
    }

    private function parseLessThan(string $column, mixed $content): WhereCondition
    {
        return WhereCondition::less($column, $content);
    }

    private function parseGreaterThan(string $column, mixed $content): WhereCondition
    {
        return WhereCondition::greater($column, $content);
    }

    private function parseLessEqualsThan(string $column, mixed $content): WhereCondition
    {
        return WhereCondition::lessEquals($column, $content);
    }

    private function parseGreaterEqualsThan(string $column, mixed $content): WhereCondition
    {
        return WhereCondition::greaterEquals($column, $content);
    }

    private function parseEnds(string $column, mixed $content, bool $caseInsensitive = true): WhereCondition
    {
        return WhereCondition::like($column, "%$content", $caseInsensitive);
    }

    private function parseEquals(string $column, mixed $content): WhereCondition
    {
        return WhereCondition::equals($column, $content);
    }

    /**
     * Treat the search query as a simple "contains" filter request over all the searchable columns.
     *
     * @return array
     */
    private function parseSearch(): array
    {
        $conditions = [];
        $searchQuery = $this->funnel->getSearch();
        foreach ($this->funnel->getSearchableColumns() as $searchableColumn) {
            $conditions[] = $this->parseContains($searchableColumn, $searchQuery);
        }
        return $conditions;
    }
}
