<?php namespace Zephyrus\Database\QueryBuilder;

class OrderByClause
{
    private string $orderBySql;

    public function __construct()
    {
        $this->orderBySql = "";
    }

    public function getSql(): string
    {
        return ((empty($this->orderBySql)) ? '' : 'ORDER BY ') . $this->orderBySql;
    }

    public function asc(string $column, bool $nullLast = true): self
    {
        $this->addOrderClause("$column ASC" . ($nullLast ? '' : " NULLS FIRST"));
        return $this;
    }

    public function desc(string $column, bool $nullFirst = true): self
    {
        $this->addOrderClause("$column DESC" . ($nullFirst ? '' : " NULLS LAST"));
        return $this;
    }

    private function addOrderClause(string $clause)
    {
        if (!empty($this->orderBySql)) {
            $this->orderBySql .= ', ';
        }
        $this->orderBySql .= $clause;
    }
}
