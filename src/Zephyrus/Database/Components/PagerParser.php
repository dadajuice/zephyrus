<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\LimitClause;
use Zephyrus\Utilities\Components\Pagination;

class PagerParser
{
    private LimitClause $limitClause;
    private int $currentPage;
    private int $limit;

    public function __construct(Pagination $pagination)
    {
        $this->currentPage = $pagination->getCurrentPage();
        $this->limit = $pagination->getLimit();
        $this->limitClause = new LimitClause();
    }

    public function hasRequested(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Retrieves the current viewing page number (as given by the Request).
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Retrieves the maximum number of rows displayed per page.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSqlClause(): LimitClause
    {
        return $this->limitClause;
    }

    /**
     * Parses the request parameters to build a corresponding PagerModel instance. The parameters should be given
     * following the public constants:.
     *
     *     example.com?page=4&limit=96
     *
     * The limit parameter is optional, as the default value (50 per page) will be used if none given. It cannot go
     * beyond the configured max limit allowed for security reason (avoid a user to manually select 15000 rows per
     * page). Developers should indicate the maximum allowed when permitting user to change the row count. By default,
     * it is limited to 50 (same as the default rows per page).
     */
    public function buildSqlClause(): LimitClause
    {
        $offset = $this->limit * ($this->currentPage - 1);
        $this->limitClause->setLimit($this->limit);
        $this->limitClause->setOffset($offset);
        return $this->limitClause;
    }
}
