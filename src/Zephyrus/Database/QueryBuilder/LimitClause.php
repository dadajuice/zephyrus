<?php namespace Zephyrus\Database\QueryBuilder;

class LimitClause
{
    private int $limit = 50;
    private int $offset = 0;

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getSql(): string
    {
        $sql = "LIMIT $this->limit";
        if ($this->offset != 0) {
            $sql .= " OFFSET $this->offset";
        }
        return $sql;
    }
}
