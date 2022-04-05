<?php namespace Zephyrus\Database\QueryBuilder;

class LimitClause
{
    private string $limitSql;

    public function __construct(int $limit, ?int $offset = null)
    {
        $this->limitSql = "LIMIT $limit";
        if (!is_null($offset)) {
            $this->limitSql .= " OFFSET $offset";
        }
    }

    public function getSql(): string
    {
        return $this->limitSql;
    }
}
