<?php namespace Zephyrus\Utilities\Listing;

class ListFunnel
{
    public const AVAILABLE_FILTERS = [
        'contains',
        'begins',
        'ends',
        'sensible_contains',
        'sensible_begins',
        'sensible_ends',
        'equals',
        'between', // used for dates or numerics
        'less', // equivalent of before for dates
        'greater', // equivalent of after for dates
        'less_equals',
        'greater_equals'
    ];

    /**
     * Raw request filters.
     *
     * @var array
     */
    private array $filters;

    /**
     * Raw request search.
     *
     * @var ?string
     */
    private ?string $search;

    /**
     * Defines if the search should be available.
     *
     * @var bool
     */
    private bool $searchEnabled = true;

    /**
     * Defines the whitelisted fields available to filter. By default, everything is allowed.
     *
     * @var array
     */
    private array $whiteList = [];

    private array $aliasColumns = [];
    private array $searchableColumns = [];

    public function __construct(array $filters, ?string $search)
    {
        $this->filters = $filters;
        $this->search = $search;
    }

    public function buildSqlParser(): SqlFunnelParser
    {
        return new SqlFunnelParser($this);
    }

    public function getFilters(?string $columnName = null): array
    {
        $filters = $this->parseFilters($this->filters);
        if (is_null($columnName)) {
            return $filters;
        }
        return $filters[$columnName] ?? [];
    }

    public function getSearch(): ?string
    {
        return $this->searchEnabled ? $this->search : null;
    }

    public function setWhiteList(array $fields): void
    {
        $this->whiteList = $fields;
    }

    public function setAliasColumns(array $aliasColumns): void
    {
        $this->aliasColumns = $aliasColumns;
    }

    public function setSearchableColumns(array $searchableColumns): void
    {
        $this->searchableColumns = $searchableColumns;
    }

    public function setSearchEnabled(bool $searchEnabled): void
    {
        $this->searchEnabled = $searchEnabled;
    }

    public function getSearchableColumns(): array
    {
        return $this->searchableColumns;
    }

    private function parseFilters(array $rawFilters): array
    {
        $filters = [];
        foreach ($rawFilters as $columnFilter => $content) {
            if (!str_contains($columnFilter, ":")) {
                $columnFilter = $columnFilter . ':contains';
            }
            list($column, $filterType) = explode(':', $columnFilter);
            if (!in_array($filterType, self::AVAILABLE_FILTERS)) {
                continue; // Ignore non-supported filter type
            }
            if ($this->whiteList && !in_array($column, $this->whiteList)) {
                continue; // Ignore non-whitelisted fields
            }
            $column = $this->aliasColumns[$column] ?? $column;
            if (!isset($filters[$column])) {
                $filters[$column] = [];
            }
            $filters[$column][] = (object) [
                'type' => $filterType,
                'value' => $content
            ];
        }
        return $filters;
    }
}
