<?php namespace Zephyrus\Utilities\Components;

use Zephyrus\Network\RequestFactory;
use Zephyrus\Network\Uri;

class PagerParser
{
    public const URL_PARAMETER = 'page';
    public const URL_LIMIT_PARAMETER = 'limit';
    public const DEFAULT_LIMIT = 50;

    private int $defaultLimit;
    private int $maxLimitAllowed;

    public function __construct(int $defaultLimit = self::DEFAULT_LIMIT, int $maxLimitAllowed = self::DEFAULT_LIMIT)
    {
        $this->defaultLimit = $defaultLimit;
        $this->maxLimitAllowed = $maxLimitAllowed;
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
        return !empty($request->getParameter(self::URL_PARAMETER, []));
    }

    /**
     * Parses the request parameters to build a corresponding PagerModel instance. The parameters should be given
     * following the public constants:
     *
     *     example.com?page=4&limit=96
     *
     * The limit parameter is optional, as the default value (50 per page) will be used if none given. It cannot go
     * beyond the configured max limit allowed for security reason (avoid a user to manually select 15000 rows per
     * page). Developers should indicate the maximum allowed when permitting user to change the row count. By default,
     * it is limited to 50 (same as the default rows per page).
     */
    public function parse(): PagerModel
    {
        $request = RequestFactory::read();
        $page = $request->getParameter(self::URL_PARAMETER, 1);
        $limit = $request->getParameter(self::URL_LIMIT_PARAMETER, $this->defaultLimit);
        $limit = min($limit, $this->maxLimitAllowed);
        $currentPage = (!is_numeric($page) || $page < 0) ? 1 : $page;
        $offset = $limit * ($currentPage - 1);

        $model = new PagerModel($limit, $offset);
        $model->setCurrentPage($currentPage);
        $model->setPageUrl($request->getUri()->getPath());
        $model->setPageQuery(Uri::removeArgument($request->getUri()->getQuery(), self::URL_PARAMETER));
        return $model;
    }
}