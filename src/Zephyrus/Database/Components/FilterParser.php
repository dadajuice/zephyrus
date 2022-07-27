<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Database\QueryBuilder\WhereCondition;
use Zephyrus\Network\RequestFactory;

class FilterParser
{
    public const URL_PARAMETER = 'filters';

    private WhereClause $whereClause;

    /**
     * Parses the request parameters to build a corresponding WHERE clause. The parameters should be given following the
     * public constants:
     *
     *     example.com?filters[column:type]=content
     *
     * .
     *
     * @param array $columnConversion
     * @return WhereClause
     */
    public function parse(array $columnConversion = []): WhereClause
    {
        $this->whereClause = new WhereClause();
        $request = RequestFactory::read();
        $filterColumns = $request->getParameter(self::URL_PARAMETER, []);
        foreach ($filterColumns as $columnDefinition => $content) {
            list($column, $filterType) = explode(':', $columnDefinition);
            // TODO: Select between or / and from received data ...
            // TODO: Validate "content" for each type of clause (ex. date_range, number, etc.)
            // TODO: Make private method for all the matches
            match ($filterType) {
                'contains' => $this->whereClause->or(WhereCondition::like($columnConversion[$column] ?? $column, "%$content%")),
                'begins' => $this->whereClause->or(WhereCondition::like($columnConversion[$column] ?? $column, "$content%")),
                'ends' => $this->whereClause->or(WhereCondition::like($columnConversion[$column] ?? $column, "%$content")),
                'equals' => $this->whereClause->or(WhereCondition::equals($columnConversion[$column] ?? $column, $content)),
            };
        }
        return $this->whereClause;
    }
}
