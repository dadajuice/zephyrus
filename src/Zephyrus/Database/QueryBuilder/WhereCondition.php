<?php namespace Zephyrus\Database\QueryBuilder;

use stdClass;

class WhereCondition
{
    private array $queryParameters;
    private string $resultSql;

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getSql(): string
    {
        return $this->resultSql;
    }

    public static function or(WhereCondition ...$conditions): self
    {
        $result = self::buildLogicalOperator('OR', $conditions);
        return new self($result->sql, $result->values);
    }

    public static function and(WhereCondition ...$conditions): self
    {
        $result = self::buildLogicalOperator('AND', $conditions);
        return new self($result->sql, $result->values);
    }

    public static function not(WhereCondition $condition): self
    {
        return new self("NOT " . $condition->resultSql, $condition->queryParameters);
    }

    public static function equals(string $column, mixed $expectedValue): self
    {
        return new self("$column = ?", $expectedValue);
    }

    public static function notEquals(string $column, mixed $expectedValue): self
    {
        return new self("$column != ?", $expectedValue);
    }

    public static function isNull(string $column): self
    {
        return new self("$column IS NULL", null);
    }

    public static function isNotNull(string $column): self
    {
        return new self("$column IS NOT NULL", null);
    }

    public static function less(string $column, mixed $expectedValue): self
    {
        return new self("$column < ?", $expectedValue);
    }

    public static function greater(string $column, mixed $expectedValue): self
    {
        return new self("$column > ?", $expectedValue);
    }

    public static function lessEquals(string $column, mixed $expectedValue): self
    {
        return new self("$column <= ?", $expectedValue);
    }

    public static function greaterEquals(string $column, mixed $expectedValue): self
    {
        return new self("$column >= ?", $expectedValue);
    }

    /**
     *
     * @see https://www.postgresql.org/docs/9.0/functions-matching.html#FUNCTIONS-SIMILARTO-REGEXP
     * @param string $column
     * @param string $sqlRegexPattern
     * @return WhereCondition
     */
    public static function similarTo(string $column, string $sqlRegexPattern): self
    {
        return new self("$column SIMILAR TO ?", $sqlRegexPattern);
    }

    public static function between(string $column, mixed $low, mixed $high): self
    {
        return new self("$column BETWEEN ? AND ?", [$low, $high]);
    }

    /**
     * Given pattern must match the LIKE operator (only wildcard characters % (sequence of zero or more characters)
     * and _ (matches any single character) accepted).
     *
     * @see https://www.postgresql.org/docs/9.0/functions-matching.html#FUNCTIONS-LIKE
     * @param string $column
     * @param string $pattern
     * @param bool $caseInsensitive
     * @return WhereCondition
     */
    public static function like(string $column, string $pattern, bool $caseInsensitive = true): self
    {
        $operator = $caseInsensitive ? 'ILIKE' : 'LIKE';
        return new self("$column $operator ?", $pattern);
    }

    public static function inArray(string $column, array $values): self
    {
        $parametricValues = str_repeat("?, ", count($values) - 1) . "?";
        return new self("$column IN($parametricValues)", $values);
    }

    /**
     * Sub query must be properly parametrized and passed as values.
     *
     * @param string $column
     * @param string $subQuery
     * @param array $values
     * @return WhereCondition
     */
    public static function inSubQuery(string $column, string $subQuery, array $values): self
    {
        return new self("$column IN($subQuery)", $values);
    }

    public static function exists(string $column, string $subQuery, array $values): self
    {
        return new self("$column EXISTS($subQuery)", $values);
    }

    public static function notExists(string $column, string $subQuery, array $values): self
    {
        return new self("$column NOT EXISTS($subQuery)", $values);
    }

    /**
     * @param string $operator
     * @param WhereCondition[] $conditions
     * @return stdClass
     */
    private static function buildLogicalOperator(string $operator, array $conditions): stdClass
    {
        $values = [];
        $sql = "";
        foreach ($conditions as $condition) {
            $values = array_merge($values, $condition->queryParameters);
            $sql .= !empty($sql) ? " $operator " . $condition->resultSql : $condition->resultSql;
        }
        return (object) [
            'values' => $values,
            'sql' => $sql
        ];
    }

    private function __construct(string $sql, mixed $value)
    {
        $this->queryParameters = [];
        if (!is_null($value)) {
            $this->queryParameters = is_array($value) ? $value : [$value];
        }
        $this->resultSql = "($sql)";
    }
}
