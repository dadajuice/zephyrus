<?php namespace Zephyrus\Utilities;

use Zephyrus\Network\Request;

class Filter
{
    const PAGE_PARAMETER_NAME = Pager::URL_PARAMETER;
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

    public function __construct(Request $request, ?string $defaultSort = null, ?string $defaultOrder = null)
    {
        $this->request = $request;
        $this->initializeQueryStrings($defaultSort, $defaultOrder);
    }

    /**
     * @return bool
     */
    public function hasSearch(): bool
    {
        return !is_null($this->search);
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

    private function initializeQueryStrings(?string $defaultSort = null, ?string $defaultOrder = null)
    {
        $this->initializeSort($defaultSort ?? "");
        $this->initializeOrder($defaultOrder ?? "asc");
        $this->initializeSearch();
        $this->initializePage();
    }

    private function initializeSort(string $defaultSort)
    {
        $this->sort = $this->request->getParameter(self::SORT_PARAMETER_NAME) ?? $defaultSort;
    }

    private function initializeOrder(string $defaultOrder)
    {
        $order = $this->request->getParameter(self::ORDER_PARAMETER_NAME) ?? $defaultOrder;
        if ($order != "asc" && $order != "desc") {
            $order = "asc";
        }
        $this->order = $order;
    }

    private function initializeSearch()
    {
        $search = $this->request->getParameter(self::SEARCH_PARAMETER_NAME);
        $this->search = !is_null($search) ? $search : null;
    }

    private function initializePage()
    {
        $page = $this->request->getParameter(self::PAGE_PARAMETER_NAME, 1);
        if (!is_numeric($page)) {
            $page = 1;
        }
        $this->page = $page;
    }
}
