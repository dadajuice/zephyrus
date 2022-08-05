<?php namespace Zephyrus\Database\Components;

use Zephyrus\Database\QueryBuilder\LimitClause;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Network\Uri;

class PagerParser
{
    public const URL_PARAMETER = 'page';
    public const URL_LIMIT_PARAMETER = 'limit';
    public const DEFAULT_LIMIT = 50;

    private LimitClause $limitClause;
    private int $defaultLimit = self::DEFAULT_LIMIT;
    private int $maxLimitAllowed = self::DEFAULT_LIMIT;
    private int $currentPage;
    private ?int $limit;
    private string $pageUrl;
    private string $pageQuery;

    public function __construct()
    {
        $request = RequestFactory::read();
        $this->currentPage = $request->getParameter(self::URL_PARAMETER, 1);
        $this->limit = $request->getParameter(self::URL_LIMIT_PARAMETER);
        $this->pageUrl = $request->getUri()->getPath();
        $this->pageQuery = Uri::removeArgument($request->getUri()->getQuery(), self::URL_PARAMETER);
        $this->limitClause = new LimitClause();
    }

    public function setDefaultLimit(int $defaultLimit)
    {
        $this->defaultLimit = $defaultLimit;
    }

    public function setMaxLimitAllowed(int $maxLimitAllowed)
    {
        $this->maxLimitAllowed = $maxLimitAllowed;
    }

    public function hasRequested(): bool
    {
        $request = RequestFactory::read();
        return !empty($request->getParameter(self::URL_PARAMETER));
    }

    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }

    public function getPageQuery(): string
    {
        return $this->pageQuery;
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
        return $this->limit ?? self::DEFAULT_LIMIT;
    }

    public function getMaxPage(int $recordCount): int
    {
        return ceil($recordCount / $this->getLimit());
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
    public function parse(): LimitClause
    {
        $this->limit = min($this->limit ?? $this->defaultLimit, $this->maxLimitAllowed);
        $currentPage = (!is_numeric($this->currentPage) || $this->currentPage < 0) ? 1 : $this->currentPage;
        $offset = $this->limit * ($currentPage - 1);
        $this->limitClause->setLimit($this->limit);
        $this->limitClause->setOffset($offset);
        return $this->limitClause;
    }
}
