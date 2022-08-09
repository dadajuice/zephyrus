<?php namespace Zephyrus\Utilities\Components;

class ListGroupView extends ListView
{
    private ?string $headerColumn = null;

    /**
     * @var callable
     */
    private $headerFormatting;

    public function __construct(array $rows)
    {
        parent::__construct($rows);
        $this->headerFormatting = function ($value) {
            return $value;
        };
    }

    public function setHeaderColumn(string $column)
    {
        $this->headerColumn = $column;
    }

    public function setHeaderFormatting(callable $callback)
    {
        $this->headerFormatting = $callback;
    }

    public function getRawRows(): array
    {
        return parent::getRows();
    }

    public function getRows(): array
    {
        if (is_null($this->headerColumn)) {
            throw new \RuntimeException("The header column property must be defined to build the group listview.");
        }

        $results = [];

        // Should be sorted by header column
        $previousTableHeader = null;
        $currentResultIndex = -1;
        foreach (parent::getRows() as $row) {
            $currentHeaderValue = ($this->headerFormatting)($row->{$this->headerColumn});
            if ($previousTableHeader != $currentHeaderValue) {
                $previousTableHeader = $currentHeaderValue;
                $currentResultIndex++;
                $results[$currentResultIndex] = (object) [
                    'header' => $currentHeaderValue,
                    'rows' => []
                ];
            }
            $results[$currentResultIndex]->rows[] = $row;
        }
        return $results;
    }
}
