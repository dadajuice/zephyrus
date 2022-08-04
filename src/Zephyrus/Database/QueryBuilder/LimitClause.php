<?php namespace Zephyrus\Database\QueryBuilder;

use Zephyrus\Database\Components\PagerParser;

class LimitClause
{
    private int $limit = PagerParser::DEFAULT_LIMIT;
    private int $offset = 0;

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public function setOffset(int $offset)
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
