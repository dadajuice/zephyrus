<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\OrderByClause;
use Zephyrus\Utilities\Components\Sort;

class SortParser
{
    private OrderByClause $orderByClause;
    private array $sorts;
    private array $aliasColumns = [];
    private bool $ascNullLast = true;
    private bool $descNullLast = false;

    public function __construct(Sort $sort)
    {
        $this->sorts = $sort->getSorts();
        $this->orderByClause = new OrderByClause();
    }

    public function hasRequested(): bool
    {
        return !empty($this->sorts);
    }

    public function setAscNullLast(bool $nullLast): void
    {
        $this->ascNullLast = $nullLast;
    }

    public function setDescNullLast(bool $nullLast): void
    {
        $this->descNullLast = $nullLast;
    }

    public function setAliasColumns(array $aliasColumns): void
    {
        $this->aliasColumns = $aliasColumns;
    }

    public function getSorts(): array
    {
        return $this->sorts;
    }

    public function getSqlClause(): OrderByClause
    {
        return $this->orderByClause;
    }

    /**
     * Parses the request parameters to build a corresponding ORDER BY clause. The parameters should be given following
     * the public constants:.
     *
     *     example.com?sorts[column] = asc|desc
     *
     * If no sorts are given, the configured default sorts will be used. The NULLs ordering is defined by the
     * setAscNullLast and setDescNullLast methods. The columnConversion array allows specifying correspondance between
     * request parameters and database column (if developers don't want to expose database column directly in UI links).
     *
     * @return OrderByClause
     */
    public function buildSqlClause(): OrderByClause
    {
        $sorts = $this->getSorts();
        foreach ($sorts as $column => $order) {
            // Invalid order are evaluated as asc sorting (default)
            match ($order) {
                'desc' => $this->orderByClause->desc($this->aliasColumns[$column] ?? $column, !$this->descNullLast),
                default => $this->orderByClause->asc($this->aliasColumns[$column] ?? $column, $this->ascNullLast),
            };
        }
        return $this->orderByClause;
    }
}
