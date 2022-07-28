<?php namespace Zephyrus\Database\Components;

use InvalidArgumentException;
use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Database\QueryBuilder\WhereCondition;
use Zephyrus\Network\RequestFactory;

class FilterParser
{
    public const URL_PARAMETER = 'filters';

    private WhereClause $whereClause;
    private array $allowedColumns = [];
    private array $aliasColumns = [];
    private string $aggregateOperator = WhereClause::OPERATOR_OR;

    public function setAllowedColumns(array $allowedColumns)
    {
        $this->allowedColumns = $allowedColumns;
    }

    public function setAliasColumns(array $aliasColumns)
    {
        $this->aliasColumns = $aliasColumns;
    }

    public function setAggregateOperator(string $operator)
    {
        if (!in_array($operator, WhereClause::SUPPORTED_OPERATORS)) {
            throw new InvalidArgumentException("Invalid operator supplied. Supported aggregate operators are [OR, AND].");
        }
        $this->aggregateOperator = $operator;
    }

    public function hasRequested(): bool
    {
        $request = RequestFactory::read();
        return !empty($request->getParameter(self::URL_PARAMETER, []));
    }

    /**
     * Parses the request parameters to build a corresponding WHERE clause. The parameters should be given following the
     * public constants:
     *
     *     example.com?filters[column:type]=content
     *
     * The aliasColumn array allows specifying correspondance between request parameters and database column (if
     * developers don't want to expose database column directly in UI links). If a specified column is not allowed it
     * will be ignored. If no column type is given, the "contains" default will be considered.
     *
     * @return WhereClause
     */
    public function parse(): WhereClause
    {
        $this->whereClause = new WhereClause();
        $request = RequestFactory::read();
        $filterColumns = $request->getParameter(self::URL_PARAMETER, []);
        foreach ($filterColumns as $columnDefinition => $content) {
            if (!str_contains($columnDefinition, ":")) {
                $columnDefinition = $columnDefinition . ':' . 'contains';
            }
            list($column, $filterType) = explode(':', $columnDefinition);
            if (!in_array($column, $this->allowedColumns)) {
                continue;
            }
            // TODO: Validate "content" for each type of clause (ex. date_range, number, etc.)
            match ($filterType) {
                'contains' => $this->parseContains($column, $content),
                'begins' => $this->parseBegins($column, $content),
                'ends' => $this->parseEnds($column, $content),
                'equals' => $this->parseEquals($column, $content),
                default => null
            };
        }
        return $this->whereClause;
    }

    private function parseContains(string $column, mixed $content)
    {
        $this->whereClause->add(WhereCondition::like($this->aliasColumns[$column] ?? $column, "%$content%"), $this->aggregateOperator);
    }

    private function parseBegins(string $column, mixed $content)
    {
        $this->whereClause->add(WhereCondition::like($this->aliasColumns[$column] ?? $column, "$content%"), $this->aggregateOperator);
    }

    private function parseEnds(string $column, mixed $content)
    {
        $this->whereClause->add(WhereCondition::like($this->aliasColumns[$column] ?? $column, "%$content"), $this->aggregateOperator);
    }

    private function parseEquals(string $column, mixed $content)
    {
        $this->whereClause->add(WhereCondition::equals($this->aliasColumns[$column] ?? $column, $content), $this->aggregateOperator);
    }
}
