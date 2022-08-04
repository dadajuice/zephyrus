<?php namespace Zephyrus\Utilities\Components;

use stdClass;
use Zephyrus\Database\Components\QueryFilter;

class ListView
{
    private array $rows;
    private array $headers = [];
    private array $additionalData = [];
    private int $count; // Count matching the filters (will be same as $totalCount if no filter are given)
    private int $totalCount; // Count total for virgin filters
    private ?QueryFilter $queryFilter = null;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->count = $this->totalCount = count($rows);
    }

    public function setQueryFilter(?QueryFilter $queryFilter)
    {
        $this->queryFilter = $queryFilter;
    }

    public function setCount(int $count, ?int $totalCount = null)
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
        if (is_null($this->queryFilter)) {
            return $data;
        }
        if (empty($data)) {
            return "";
        }
        $pattern = "/" . preg_quote($this->queryFilter->getSearch(), '/') . "/i";
        return (!$this->queryFilter->getSearch()) ? $data : preg_replace($pattern, "<mark>$0</mark>", $data);
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $label
     * @param string|null $sort
     * @param string $align
     */
    public function addHeader(string $label, ?string $sort = null, string $align = "")
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
        return $this->queryFilter->getPagerParser()->getCurrentPage();
    }

    public function getSearch(): string
    {
        return $this->queryFilter->getFilterParser()->getSearch();
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getRow(int $index): ?stdClass
    {
        return $this->rows[$index] ?? null;
    }

    public function getPager(): ?PagerView
    {
        return new PagerView($this->queryFilter->getPagerParser(), $this->count);
    }

    public function addAdditionalData(string $key, $value)
    {
        $this->additionalData[$key] = $value;
    }

    public function setAdditionalData(array $data)
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
                'totalCount' => $this->totalCount,
            ],
            'filter' => [
                'search' => $this->getSearch(),
                'sort' => $this->getSort(),
                'order' => $this->getOrder()
            ]
        ];
        if ($this->pagerLimit > 0) {
            $json['pager'] = [
                'maxPage' => $this->pager->getMaxPage(),
                'currentPage' => $this->pager->getCurrentPage(),
                'maxEntitiesPerPage' => $this->pager->getMaxEntitiesPerPage()
            ];
        }
        if (!empty($this->additionalData)) {
            $json['data'] = $this->additionalData;
        }
        return $json;
    }
}
