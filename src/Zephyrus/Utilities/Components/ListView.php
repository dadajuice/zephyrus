<?php namespace Zephyrus\Utilities\Components;

use stdClass;

class ListView
{
    private array $rows;
    private array $headers = [];
    private array $additionalData = [];
    private int $count; // Count matching the filters (will be same as $totalCount if no filter are given)
    private int $totalCount; // Count total for virgin filters
    private ?ListFilter $filter = null;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->count = $this->totalCount = count($rows);
    }

    public function setFilter(?ListFilter $filter): void
    {
        $this->filter = $filter;
    }

    public function setCount(int $count, ?int $totalCount = null): void
    {
        $this->count = $count;
        $this->totalCount = $totalCount ?? $count;
    }

    /**
     * Highlights matching search words into the given data string using the <mark> HTML tag (which can then easily be
     * visually customized in CSS).
     *
     * @param string|null $data
     * @return string
     */
    public function mark(?string $data): string
    {
        $search = $this->filter?->getFunnel()->getSearch();
        if (is_null($search)) {
            if (is_null($data)) {
                return "";
            }
            return $data;
        }
        if (empty($data)) {
            return "";
        }
        $pattern = "/" . preg_quote($search, '/') . "/i";
        return preg_replace($pattern, "<mark>$0</mark>", $data);
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $label
     * @param string|null $sort
     * @param string $align
     */
    public function addHeader(string $label, ?string $sort = null, string $align = ""): void
    {
        $this->headers[] = (object) [
            'title' => $label,
            'sort' => $sort,
            'align' => $align
        ];
    }

    /**
     * @return stdClass[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function getCurrentPage(): int
    {
        return $this->filter?->getPagination()->getCurrentPage() ?? 1;
    }

    public function getLimit(): int
    {
        return $this->filter?->getPagination()->getLimit() ?? Pagination::DEFAULT_LIMIT;
    }

    public function getSearch(): ?string
    {
        return $this->filter?->getFunnel()->getSearch();
    }

    public function getFilters(): ?array
    {
        return $this->filter?->getFunnel()->getFilters();
    }

    public function getSorts(): ?array
    {
        return $this->filter?->getSort()->getSorts();
    }

    public function getCurrentRowCount(): int
    {
        return $this->count;
    }

    public function getTotalRowCount(): int
    {
        return $this->totalCount;
    }

    public function getRow(int $index): ?stdClass
    {
        return $this->rows[$index] ?? null;
    }

    public function getPager(): PagerView
    {
        return new PagerView($this->filter->getPagination(), $this->totalCount);
    }

    public function addAdditionalData(string $key, mixed $value): void
    {
        $this->additionalData[$key] = $value;
    }

    public function setAdditionalData(array $data): void
    {
        $this->additionalData = $data;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function toArray(): array
    {
        $json = [
            'results' => [
                'rows' => $this->rows,
                'count' => $this->count,
                'total_count' => $this->totalCount,
            ],
            'filter' => [
                'search' => $this->getSearch(),
                'sorts' => $this->getSorts(),
                'filters' => $this->getFilters()
            ],
            'pager' => [
                'max_page' => ceil($this->totalCount / $this->getLimit()),
                'current_page' => $this->getCurrentPage(),
                'limit' => $this->getLimit()
            ]
        ];
        if (!empty($this->additionalData)) {
            $json['data'] = $this->additionalData;
        }
        return $json;
    }
}
