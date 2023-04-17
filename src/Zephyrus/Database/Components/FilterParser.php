<?php namespace Zephyrus\Database\Components;

use InvalidArgumentException;
use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Database\QueryBuilder\WhereCondition;
use Zephyrus\Utilities\Components\Funnel;

class FilterParser
{
    private WhereClause $whereClause;
    private string $searchQuery;
    private array $filters;
    private array $aliasColumns = [];
    private array $searchableColumns = [];
    private string $aggregateOperator = WhereClause::OPERATOR_AND;

    public function __construct(Funnel $funnel)
    {
        $this->whereClause = new WhereClause();
        $this->searchQuery = $funnel->getSearch() ?? "";
        $this->filters = $funnel->getFilters();
    }

    public function setAliasColumns(array $aliasColumns): void
    {
        $this->aliasColumns = $aliasColumns;
    }

    public function setSearchableColumns(array $searchableColumns): void
    {
        $this->searchableColumns = $searchableColumns;
    }

    public function setAggregateOperator(string $operator): void
    {
        if (!in_array($operator, WhereClause::SUPPORTED_OPERATORS)) {
            throw new InvalidArgumentException("Invalid operator supplied. Supported aggregate operators are [OR, AND].");
        }
        $this->aggregateOperator = $operator;
    }

    public function getSearch(): string
    {
        return $this->searchQuery;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSqlClause(): WhereClause
    {
        return $this->whereClause;
    }

    public function hasRequested(): bool
    {
        return !empty($this->filters) || !empty($this->searchQuery);
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
        if (!empty($this->searchQuery)) {
            $searchCondition = call_user_func_array([WhereCondition::class, "or"], $this->parseSearch());
            $this->whereClause->add($searchCondition, $this->aggregateOperator);
        }
        return $this->whereClause;
    }

    private function parseFilters(): array
    {
        $conditions = [];
        foreach ($this->filters as $columnDefinition => $content) {
            list($column, $filterType) = explode(':', $columnDefinition);
            match ($filterType) {
                'contains' => $conditions[] = $this->parseContains($column, $content),
                'begins' => $conditions[] = $this->parseBegins($column, $content),
                'ends' => $conditions[] = $this->parseEnds($column, $content),
                'sensible-contains' => $conditions[] = $this->parseContains($column, $content, false),
                'sensible-begins' => $conditions[] = $this->parseBegins($column, $content, false),
                'sensible-ends' => $this->parseEnds($column, $content, false),
                'equals' => $conditions[] = $this->parseEquals($column, $content),
                'between' => $conditions[] = $this->parseBetween($column, $content),
                default => null
            };
        }
        return $conditions;
    }

    private function parseBetween(string $column, mixed $content): WhereCondition
    {
        $contents = explode("~", $content);
        //$this->whereClause->add(WhereCondition::between($this->aliasColumns[$column] ?? $column, $contents[0], $contents[1]), $this->aggregateOperator);
        return WhereCondition::between($this->aliasColumns[$column] ?? $column, $contents[0], $contents[1]);
    }

    private function parseContains(string $column, mixed $content, bool $caseInsensitive = true): WhereCondition
    {
        return WhereCondition::like($this->aliasColumns[$column] ?? $column, "%$content%", $caseInsensitive);
    }

    private function parseBegins(string $column, mixed $content, bool $caseInsensitive = true): WhereCondition
    {
        return WhereCondition::like($this->aliasColumns[$column] ?? $column, "$content%", $caseInsensitive);
    }

    private function parseEnds(string $column, mixed $content, bool $caseInsensitive = true): WhereCondition
    {
        return WhereCondition::like($this->aliasColumns[$column] ?? $column, "%$content", $caseInsensitive);
    }

    private function parseEquals(string $column, mixed $content): WhereCondition
    {
        return WhereCondition::equals($this->aliasColumns[$column] ?? $column, $content);
    }

    /**
     * Treat the search query as a simple "contains" filter request over all the searchable columns.
     *
     * @return array
     */
    private function parseSearch(): array
    {
        $conditions = [];
        foreach ($this->searchableColumns as $searchableColumn) {
            $conditions[] = $this->parseContains($searchableColumn, $this->searchQuery);
        }
        return $conditions;
    }
}
