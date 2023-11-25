<?php namespace Zephyrus\Utilities\Listing;

use InvalidArgumentException;
use Zephyrus\Network\Request\QueryString;

class ListPagination
{
    public const DEFAULT_LIMIT = 50;

    private ?int $currentPage;
    private ?int $limit;
    private int $defaultLimit = self::DEFAULT_LIMIT;
    private int $maxLimitAllowed = self::DEFAULT_LIMIT;

    public function __construct(mixed $currentPage, mixed $limit)
    {
        $this->currentPage = $this->parseValue($currentPage);
        $this->limit = $this->parseValue($limit);
    }

    public function getCurrentPage(): int
    {
        return (!is_numeric($this->currentPage) || $this->currentPage < 0) ? 1 : $this->currentPage;
    }

    public function getLimit(): int
    {
        return min($this->limit ?? $this->defaultLimit, $this->maxLimitAllowed);
    }

    public function getMaxLimit(): int
    {
        return $this->maxLimitAllowed;
    }

    public function getMaxPage(int $recordCount): int
    {
        return ceil($recordCount / $this->getLimit()) ?: 1;
    }

    public function setDefaultLimit(int $defaultLimit, int $maxLimitAllowed): void
    {
        if ($maxLimitAllowed < $defaultLimit) {
            throw new InvalidArgumentException("The limit must be greater or equal to the default limit.");
        }
        $this->defaultLimit = $defaultLimit;
        $this->maxLimitAllowed = $maxLimitAllowed;
    }

    public function getPagerQuery(string $rawQueryString): string
    {
        return (new QueryString($rawQueryString))
            ->removeArgumentEquals('page')
            ->buildString();
    }

    private function parseValue(mixed $rawPage): ?int
    {
        if (!is_numeric($rawPage ?? "")) {
            return null;
        }
        return (int) $rawPage;
    }
}
