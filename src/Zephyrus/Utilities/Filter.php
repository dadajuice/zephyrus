<?php namespace Zephyrus\Utilities;

use Zephyrus\Network\Request;

class Filter
{
    const PAGE_PARAMETER_NAME = Pager::DEFAULT_URL_PARAMETER;
    const SORT_PARAMETER_NAME = "sort";
    const ORDER_PARAMETER_NAME = "order";
    const SEARCH_PARAMETER_NAME = "search";

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $sort;

    /**
     * @var string
     */
    private $order;

    /**
     * @var string
     */
    private $search;

    /**
     * @var int
     */
    private $page;

    /**
     * @var string
     */
    private $defaultSort;

    /**
     * @var string
     */
    private $defaultOrder;

    public function __construct(Request $request, string $defaultSort = "", string $defaultOrder = "asc")
    {
        $this->request = $request;
        $this->defaultOrder = $defaultOrder;
        $this->defaultSort = $defaultSort;
        $this->initializeQueryStrings();
    }

    /**
     * @return bool
     */
    public function hasSearch(): bool
    {
        return !is_null($this->search);
    }

    /**
     * @return bool
     */
    public function hasSort(): bool
    {
        return !empty($this->sort);
    }

    /**
     * @return string
     */
    public function getSort(): string
    {
        return $this->sort;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getSearch(): string
    {
        return $this->search ?? "";
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    private function initializeQueryStrings()
    {
        $this->initializeSort();
        $this->initializeOrder();
        $this->initializeSearch();
        $this->initializePage();
    }

    /**
     * Initializes the sort property according to the request. Will use the
     * configured default sort if none provided in the request.
     */
    private function initializeSort()
    {
        $this->sort = $this->request->getParameter(self::SORT_PARAMETER_NAME) ?? $this->defaultSort;
    }

    /**
     * Initializes the order property according to the request and makes sure
     * to always have either asc or desc. Will use the configured default sort
     * if none provided in the request.
     */
    private function initializeOrder()
    {
        $order = $this->request->getParameter(self::ORDER_PARAMETER_NAME) ?? $this->defaultOrder;
        if ($order != "asc" && $order != "desc") {
            $order = "asc";
        }
        $this->order = $order;
    }

    /**
     * Initializes the search property according to the request.
     */
    private function initializeSearch()
    {
        $this->search = $this->request->getParameter(self::SEARCH_PARAMETER_NAME);
    }

    /**
     * Initializes the page property according to the request and makes sure to
     * be on page 1 if the given page is not numeric.
     */
    private function initializePage()
    {
        $page = $this->request->getParameter(self::PAGE_PARAMETER_NAME, 1);
        if (!is_numeric($page)) {
            $page = 1;
        }
        $this->page = $page;
    }
}
