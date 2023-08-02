<?php namespace Zephyrus\Utilities\Listing;

use Zephyrus\Network\QueryString;

class ListSort
{
    private array $sorts;
    private array $defaultSorts = [];
    private array $whiteList = [];
    private array $aliasColumns = [];
    private bool $ascNullLast = true;
    private bool $descNullLast = false;

    public function __construct(array $sorts = [])
    {
        $this->sorts = $sorts;
    }

    public function getSorts(): array
    {
        $sorts = empty($this->sorts) ? $this->defaultSorts : $this->sorts;
        $allowedFields = $this->whiteList;
        $sorts = array_filter($sorts, function ($value, $field) use ($allowedFields) {
            return in_array($value, ['asc', 'desc']) && (!$allowedFields || in_array($field, $allowedFields));
        }, ARRAY_FILTER_USE_BOTH);
        return $this->updateAliasColumns($sorts);
    }

    public function setAliasColumns(array $aliasColumns): void
    {
        $this->aliasColumns = $aliasColumns;
    }

    public function setDefaults(array $defaultSorts): void
    {
        $this->defaultSorts = $defaultSorts;
    }

    public function setWhiteList(array $fields): void
    {
        $this->whiteList = $fields;
    }

    public function setAscNullLast(bool $nullLast): void
    {
        $this->ascNullLast = $nullLast;
    }

    public function setDescNullLast(bool $nullLast): void
    {
        $this->descNullLast = $nullLast;
    }

    /**
     * @return bool
     */
    public function isAscNullLast(): bool
    {
        return $this->ascNullLast;
    }

    /**
     * @return bool
     */
    public function isDescNullLast(): bool
    {
        return $this->descNullLast;
    }

    public function getLimitQueryArguments(string $rawQueryString): array
    {
        return (new QueryString($rawQueryString))
            ->removeArgumentEquals('page')
            ->removeArgumentEquals('limit')
            ->getArguments();
    }

    public function getSortQueryArguments(string $rawQueryString): array
    {
        return (new QueryString($rawQueryString))
            ->removeArgumentEquals('page')
            ->removeArgumentStartsWith('sorts[')
            ->getArguments();
    }

    private function updateAliasColumns(array $sorts): array
    {
        foreach ($this->aliasColumns as $aliasColumn => $realColumn) {
            $sorts = $this->updateAliasColumn($sorts, $aliasColumn, $realColumn);
        }
        return $sorts;
    }

    private function updateAliasColumn(array $array, string $oldKey, string $newKey): array
    {
        if (array_key_exists($oldKey, $array)) {
            $oldKeyPosition = array_search($oldKey, array_keys($array));
            $arrayKeys = array_keys($array);
            $arrayKeys[$oldKeyPosition] = $newKey;
            return array_combine($arrayKeys, $array);
        }
        return $array;
    }
}
