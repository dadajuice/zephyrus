<?php namespace Zephyrus\Database\QueryBuilder;

class WhereClause
{
    public const OPERATOR_AND = "AND";
    public const OPERATOR_OR = "OR";
    private const SUPPORTED_OPERATORS = [self::OPERATOR_AND, self::OPERATOR_OR];

    private string $whereClause = "";
    private array $queryParameters = [];

    public function __construct(?WhereCondition $baseCondition = null)
    {
        if (!is_null($baseCondition)) {
            $this->concatCondition($baseCondition);
        }
    }

    public function and(WhereCondition $condition): self
    {
        return $this->add($condition, self::OPERATOR_AND);
    }

    public function or(WhereCondition $condition): self
    {
        return $this->add($condition, self::OPERATOR_OR);
    }

    public function add(WhereCondition $condition, string $logicalOperator): self
    {
        $logicalOperator = strtoupper($logicalOperator);
        if (!in_array($logicalOperator, self::SUPPORTED_OPERATORS)) {
            throw new \InvalidArgumentException("Logical operator must be either AND or OR.");
        }
        if (!empty($this->whereClause)) {
            $this->whereClause .= " $logicalOperator ";
        }
        $this->concatCondition($condition);
        return $this;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getSql(): string
    {
        return ((empty($this->whereClause)) ? '' : 'WHERE ') . $this->whereClause;
    }

    private function concatCondition(WhereCondition $baseCondition)
    {
        $this->queryParameters = array_merge($this->queryParameters, $baseCondition->getQueryParameters());
        $this->whereClause .= $baseCondition->getSql();
    }
}
