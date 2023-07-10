<?php namespace Zephyrus\Utilities\Listing;

use stdClass;

class ListView
{
    private array $rows;
    private array $headers = [];
    private array $additionalData = [];
    private int $count;
    private ?ListModel $model = null;

    /**
     * @var FilterView[]
     */
    private array $filterViews = [];

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Applies the total number of rows for the listing. Doesn't represent the current result set count which can have
     * a limited number of rows configured.
     *
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    /**
     * Applies the ListModel instance used to generate the list view if applicable. Useful to easily retrieve any
     * list filtering settings that may have applied.
     *
     * @param ListModel $model
     */
    public function setModel(ListModel $model): void
    {
        $this->model = $model;
    }

    /**
     * @param FilterView[] $filterViews
     */
    public function setFilterViews(array $filterViews): void
    {
        $this->filterViews = $filterViews;
    }

    /**
     * Introduces the <mark> HTML tags around the matching search words in the given data string. All searchable column
     * values should be rendered with this method to ensure uniformity. E.g list.mark(row.example).
     *
     * @param string|null $data
     * @return string
     */
    public function mark(?string $data): string
    {
        $search = $this->model?->getFunnel()->getSearch();
        if (is_null($search)) {
            return (is_null($data)) ? "" : $data;
        }
        if (empty($data)) {
            return "";
        }
        $pattern = "/" . preg_quote($search, '/') . "/iu";
        return preg_replace($pattern, "<mark>$0</mark>", $data);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getFilterViews(): array
    {
        return $this->filterViews;
    }

    public function getOptionView(): OptionView
    {
        return new OptionView();
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
     * Retrieves the entire rows of the configured result set as an array of stdClass containing all the required
     * columns.
     *
     * @return stdClass[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Retrieves a specific row from a specified index (row order). Returns null if the index is non-existant.
     *
     * @param int $index
     * @return stdClass|null
     */
    public function getRow(int $index): ?stdClass
    {
        return $this->rows[$index] ?? null;
    }

    public function getCurrentPage(): int
    {
        return $this->model?->getPagination()->getCurrentPage();
    }

    public function getMaxPage(): int
    {
        return $this->model?->getPagination()->getMaxPage($this->count);
    }

    public function getLimit(): int
    {
        return $this->model?->getPagination()->getLimit();
    }

    public function getMaxLimit(): int
    {
        return $this->model?->getPagination()->getMaxLimit();
    }

    public function getSearch(): ?string
    {
        return $this->model?->getFunnel()->getSearch();
    }

    public function getFilters(?string $column = null): array
    {
        return $this->model?->getFunnel()->getFilters($column);
    }

    public function getSorts(): array
    {
        return $this->model?->getSort()->getSorts();
    }

    public function getCurrentRowCount(): int
    {
        return $this->count;
    }

    public function getModel(): ?ListModel
    {
        return $this->model;
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
                'count' => $this->count
            ],
            'filter' => [
                'search' => $this->getSearch(),
                'sorts' => $this->getSorts(),
                'filters' => $this->getFilters()
            ],
            'pager' => [
                'max_page' => $this->getMaxPage(),
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
