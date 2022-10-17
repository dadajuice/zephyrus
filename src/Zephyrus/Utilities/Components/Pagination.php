<?php namespace Zephyrus\Utilities\Components;

use InvalidArgumentException;

class Pagination
{
    public const DEFAULT_LIMIT = 50;

    private ?int $currentPage;
    private ?int $limit;
    private int $defaultLimit = self::DEFAULT_LIMIT;
    private int $maxLimitAllowed = self::DEFAULT_LIMIT;
    private string $pageParameterName = FilterConfiguration::DEFAULT_PAGE_PARAM;
    private string $limitParameterName = FilterConfiguration::DEFAULT_LIMIT_PARAM;

    public function __construct(?int $currentPage, ?int $limit)
    {
        $this->currentPage = $currentPage;
        $this->limit = $limit;
    }

    public function getCurrentPage(): int
    {
        return (!is_numeric($this->currentPage) || $this->currentPage < 0) ? 1 : $this->currentPage;
    }

    public function getLimit(): int
    {
        return min($this->limit ?? $this->defaultLimit, $this->maxLimitAllowed);
    }

    public function getMaxPage(int $recordCount): int
    {
        return ceil($recordCount / $this->getLimit());
    }

    public function getPageParameterName(): string
    {
        return $this->pageParameterName;
    }

    public function getLimitParameterName(): string
    {
        return $this->limitParameterName;
    }

    public function setDefaultLimit(int $defaultLimit, int $maxLimitAllowed): void
    {
        if ($maxLimitAllowed < $defaultLimit) {
            throw new InvalidArgumentException("The maximum allowed limit must be greater or equal to the default limit.");
        }
        $this->defaultLimit = $defaultLimit;
        $this->maxLimitAllowed = $maxLimitAllowed;
    }

    public function setCurrentPage(?int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function setParameterNames(string $pageParameterName, string $limitParameterName): void
    {
        $this->pageParameterName = $pageParameterName;
        $this->limitParameterName = $limitParameterName;
    }
}
