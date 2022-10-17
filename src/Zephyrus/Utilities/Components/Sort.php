<?php namespace Zephyrus\Utilities\Components;

class Sort
{
    private ?array $allowedFields = null;
    private array $defaultSorts = [];
    private ?array $sorts;
    private string $sortParameterName = FilterConfiguration::DEFAULT_SORTS_PARAM;

    public function __construct(?array $sorts = null)
    {
        $this->sorts = $sorts;
    }

    public function isDefined(): bool
    {
        return !is_null($this->sorts);
    }

    public function setAllowedFields(array $fields): void
    {
        $this->allowedFields = $fields;
    }

    /**
     * Retrieves the sorting filters if included in the request (or manually given). If no sorting filter are given, the
     * default ones are returned (defaults to empty []). Useful for list with a default sort.
     *
     * @return array
     */
    public function getSorts(): array
    {
        $sorts = $this->sorts ?? $this->defaultSorts;
        $allowedFields = $this->allowedFields;
        if (!is_null($allowedFields)) {
            $sorts = array_filter($sorts, function ($field) use ($allowedFields) {
                return in_array($field, $allowedFields);
            }, ARRAY_FILTER_USE_KEY);
        }
        return $sorts;
    }

    public function getSortParameterName(): string
    {
        return $this->sortParameterName;
    }

    public function setSorts(?array $sorts): void
    {
        $this->sorts = $sorts;
    }

    public function setDefaultSorts(array $defaultSorts): void
    {
        $this->defaultSorts = $defaultSorts;
    }

    public function setParameterName(string $sortParameterName): void
    {
        $this->sortParameterName = $sortParameterName;
    }
}
