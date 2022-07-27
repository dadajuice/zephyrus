<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\OrderByClause;
use Zephyrus\Network\RequestFactory;

class SortParser
{
    public const URL_PARAMETER = 'sorts';

    private OrderByClause $orderByClause;
    private array $defaultSorts;
    private bool $ascNullLast = true;
    private bool $descNullLast = false;

    public function __construct(array $defaultSorts = [])
    {
        $this->defaultSorts = $defaultSorts;
    }

    public function setAscNullLast(bool $nullLast)
    {
        $this->ascNullLast = $nullLast;
    }

    public function setDescNullLast(bool $nullLast)
    {
        $this->descNullLast = $nullLast;
    }

    /**
     * Parses the request parameters to build a corresponding ORDER BY clause. The parameters should be given following
     * the public constants:
     *
     *     example.com?sorts[column] = asc|desc
     *
     * If no sorts are given, the configured default sorts will be used. The NULLs ordering is defined by the
     * setAscNullLast and setDescNullLast methods.
     *
     * @param array $columnConversion
     * @return OrderByClause
     */
    public function parse(array $columnConversion = []): OrderByClause
    {
        $request = RequestFactory::read();
        $sortColumns = $request->getParameter(self::URL_PARAMETER, $this->defaultSorts);
        $this->orderByClause = new OrderByClause();
        foreach ($sortColumns as $column => $order) {
            // Invalid order are evaluated as asc sorting (default)
            match ($order) {
                'desc' => $this->orderByClause->desc($columnConversion[$column] ?? $column, !$this->descNullLast),
                default => $this->orderByClause->asc($columnConversion[$column] ?? $column, $this->ascNullLast),
            };
        }
        return $this->orderByClause;
    }
}
