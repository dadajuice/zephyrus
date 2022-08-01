<?php namespace Zephyrus\Database\QueryBuilder;

use Zephyrus\Database\Core\Adapters\DatabaseAdapter;

class LimitClause
{
    private int $limit;
    private ?int $offset;

    public function __construct(int $limit, ?int $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function getSql(DatabaseAdapter $adapter): string
    {
        return $adapter->getSqlLimit($this->limit, $this->offset);
    }
}
