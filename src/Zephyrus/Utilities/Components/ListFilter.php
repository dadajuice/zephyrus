<?php namespace Zephyrus\Utilities\Components;

class ListFilter
{
    private Funnel $funnel;
    private Sort $sort;
    private Pagination $pagination;

    public function __construct(?FilterConfiguration $configuration = null)
    {
        if (!is_null($configuration)) {
            $this->funnel = $configuration->parseFunnel();
            $this->sort = $configuration->parseSort();
            $this->pagination = $configuration->parsePagination();
        }
    }

    public function getSort(): Sort
    {
        return $this->sort;
    }

    public function getFunnel(): Funnel
    {
        return $this->funnel;
    }

    public function getPagination(): Pagination
    {
        return $this->pagination;
    }

    public function setSort(Sort $sort): void
    {
        $this->sort = $sort;
    }

    public function setFunnel(Funnel $funnel): void
    {
        $this->funnel = $funnel;
    }

    public function setPagination(Pagination $pagination): void
    {
        $this->pagination = $pagination;
    }
}
