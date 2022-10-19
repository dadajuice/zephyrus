<?php namespace Zephyrus\Utilities\Components;

use Zephyrus\Network\Request;
use Zephyrus\Network\Uri;

class FilterConfiguration
{
    public const DEFAULT_FILTERS_PARAM = 'filters';
    public const DEFAULT_SEARCH_PARAM = 'search';
    public const DEFAULT_SORTS_PARAM = 'sorts';
    public const DEFAULT_PAGE_PARAM = 'page';
    public const DEFAULT_LIMIT_PARAM = 'limit';

    private Request $request;
    private string $filterParameter = self::DEFAULT_FILTERS_PARAM;
    private string $searchParameter = self::DEFAULT_SEARCH_PARAM;
    private string $sortParameter = self::DEFAULT_SORTS_PARAM;
    private string $pageParameter = self::DEFAULT_PAGE_PARAM;
    private string $limitParameter = self::DEFAULT_LIMIT_PARAM;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function parseSort(): Sort
    {
        $sort = new Sort($this->parseSorts());
        $sort->setParameterName($this->sortParameter);
        return $sort;
    }

    public function parseFunnel(): Funnel
    {
        $funnel = new Funnel($this->parseFilters(), $this->parseSearch());
        $funnel->setParameterNames($this->filterParameter, $this->searchParameter);
        return $funnel;
    }

    public function parsePagination(): Pagination
    {
        $pagination = new Pagination($this->parsePage(), $this->parseLimit());
        $pagination->setParameterNames($this->pageParameter, $this->limitParameter);
        return $pagination;
    }

    public function getUri(): Uri
    {
        return $this->request->getUri();
    }

    public function setFunnelParameters(string $filterParam, string $searchParam = self::DEFAULT_SEARCH_PARAM): void
    {
        $this->filterParameter = $filterParam;
        $this->searchParameter = $searchParam;
    }

    public function setSortParameter(string $sortParam): void
    {
        $this->sortParameter = $sortParam;
    }

    public function setPaginationParameters(string $pageParam, string $limitParam = self::DEFAULT_LIMIT_PARAM): void
    {
        $this->pageParameter = $pageParam;
        $this->limitParameter = $limitParam;
    }

    private function parsePage(): ?int
    {
        $source = $this->request->getParameter($this->pageParameter);
        if (!ctype_digit($source ?? "")) {
            return null;
        }
        return $source;
    }

    private function parseLimit(): ?int
    {
        $source = $this->request->getParameter($this->limitParameter);
        if (!ctype_digit($source ?? "")) {
            return null;
        }
        return $source;
    }

    private function parseSorts(): ?array
    {
        $source = $this->request->getParameter($this->sortParameter);
        if (!is_array($source)) {
            return null;
        }
        return $source;
    }

    private function parseFilters(): ?array
    {
        $source = $this->request->getParameter($this->filterParameter);
        if (!is_array($source)) {
            return null;
        }
        return $source;
    }

    private function parseSearch(): ?string
    {
        return $this->request->getParameter($this->searchParameter);
    }
}
