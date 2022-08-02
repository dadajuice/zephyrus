<?php namespace Zephyrus\Database\QueryBuilder;

class LimitClause
{
    private int $limit;
    private ?int $offset;

    public function __construct(int $limit, ?int $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function getSql(): string
    {
        $sql = "LIMIT $this->limit";
        if (!is_null($this->offset)) {
            $sql .= " OFFSET $this->offset";
        }
        return $sql;
    }
}
