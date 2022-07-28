<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\OrderByClause;
use Zephyrus\Network\RequestFactory;

class SortParser
{
    public const URL_PARAMETER = 'sorts';

    private OrderByClause $orderByClause;
    private array $allowedColumns = [];
    private array $defaultSorts = [];
    private array $aliasColumns = [];
    private bool $ascNullLast = true;
    private bool $descNullLast = false;

    public function setAscNullLast(bool $nullLast)
    {
        $this->ascNullLast = $nullLast;
    }

    public function setDescNullLast(bool $nullLast)
    {
        $this->descNullLast = $nullLast;
    }

    public function setAliasColumns(array $aliasColumns)
    {
        $this->aliasColumns = $aliasColumns;
    }

    public function setAllowedColumns(array $allowedColumns)
    {
        $this->allowedColumns = $allowedColumns;
    }

    public function setDefaultSort(array $defaultSorts)
    {
        $this->defaultSorts = $defaultSorts;
    }

    public function hasRequested(): bool
    {
        $request = RequestFactory::read();
        return !empty($request->getParameter(self::URL_PARAMETER, []));
    }

    /**
     * Parses the request parameters to build a corresponding ORDER BY clause. The parameters should be given following
     * the public constants:
     *
     *     example.com?sorts[column] = asc|desc
     *
     * If no sorts are given, the configured default sorts will be used. The NULLs ordering is defined by the
     * setAscNullLast and setDescNullLast methods. The columnConversion array allows specifying correspondance between
     * request parameters and database column (if developers don't want to expose database column directly in UI links).
     *
     * @return OrderByClause
     */
    public function parse(): OrderByClause
    {
        $request = RequestFactory::read();
        $sortColumns = $request->getParameter(self::URL_PARAMETER, $this->defaultSorts);
        $this->orderByClause = new OrderByClause();
        foreach ($sortColumns as $column => $order) {
            if (!in_array($column, $this->allowedColumns)) {
                continue;
            }
            // Invalid order are evaluated as asc sorting (default)
            match ($order) {
                'desc' => $this->orderByClause->desc($this->aliasColumns[$column] ?? $column, !$this->descNullLast),
                default => $this->orderByClause->asc($this->aliasColumns[$column] ?? $column, $this->ascNullLast),
            };
        }
        return $this->orderByClause;
    }
}
