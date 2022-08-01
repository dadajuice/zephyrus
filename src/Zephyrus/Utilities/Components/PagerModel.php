<?php namespace Zephyrus\Utilities\Components;

use Zephyrus\Database\QueryBuilder\LimitClause;

class PagerModel
{
    private int $currentPage = 1;
    private int $limit;
    private int $offset;
    private string $pageUrl = "#";
    private string $pageQuery = "";

    public function __construct(int $limit = PagerParser::DEFAULT_LIMIT, int $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function buildView(int $recordCount): PagerView
    {
        return new PagerView($this, $recordCount);
    }

    public function buildLimitClause(): LimitClause
    {
        return new LimitClause($this->limit, $this->offset);
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function setPageUrl(string $pageUrl): void
    {
        $this->pageUrl = $pageUrl;
    }

    public function setPageQuery(string $pageQuery): void
    {
        $this->pageQuery = $pageQuery;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }

    public function getPageQuery(): string
    {
        return $this->pageQuery;
    }

//    public function getSqlLimitClause(DatabaseAdapter $adapter)
//    {
//        return $adapter->getLimitClause($this->offset, $this->getMaxEntitiesPerPage());
//    }
}
