<?php namespace Zephyrus\Utilities\Components;

class Funnel
{
    public const AVAILABLE_FILTERS = [
        'contains',
        'begins',
        'ends',
        'sensible-contains',
        'sensible-begins',
        'sensible-ends',
        'equals',
        'between'
    ];
    private ?array $allowedFields = null;
    private ?array $filters;
    private ?string $search;
    private bool $searchEnabled = true;
    private string $filterParameterName = FilterConfiguration::DEFAULT_FILTERS_PARAM;
    private string $searchParameterName = FilterConfiguration::DEFAULT_SEARCH_PARAM;

    public function __construct(?array $filters = null, ?string $search = null)
    {
        $this->filters = $filters;
        $this->search = $search;
    }

    public function isDefined(): bool
    {
        return !is_null($this->filters) || !is_null($this->search);
    }

    public function setAllowedFields(array $fields): void
    {
        $this->allowedFields = $fields;
    }

    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->filters ?? [] as $columnFilter => $content) {
            if (!str_contains($columnFilter, ":")) {
                $columnFilter = $columnFilter . ':' . 'contains';
            }
            list($column, $filterType) = explode(':', $columnFilter);
            if ((!is_null($this->allowedFields) && !in_array($column, $this->allowedFields))
                || !in_array($filterType, self::AVAILABLE_FILTERS)) {
                continue;
            }
            $filters[$columnFilter] = $content;
        }
        return $filters;
    }

    public function getSearch(): ?string
    {
        return $this->searchEnabled ? $this->search : null;
    }

    public function getFilterParameterName(): string
    {
        return $this->filterParameterName;
    }

    public function getSearchParameterName(): string
    {
        return $this->searchParameterName;
    }

    public function setFilters(?array $filters): void
    {
        $this->filters = $filters;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }

    public function setSearchEnabled(bool $searchEnabled): void
    {
        $this->searchEnabled = $searchEnabled;
    }

    public function setParameterNames(string $filterParameterName, string $searchParameterName): void
    {
        $this->filterParameterName = $filterParameterName;
        $this->searchParameterName = $searchParameterName;
    }
}
