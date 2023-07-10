<?php namespace Zephyrus\Utilities\Listing;

use RuntimeException;

class ListGroupView extends ListView
{
    /**
     * Column name from the given result set that should be used as the list header. Must exists as part of the given
     * rows.
     *
     * @var string
     */
    private string $headerColumn;

    /**
     * Defines the callback function for header formatting (optional, default to simply the header value). For example,
     * useful for date time formatted column.
     *
     * @var callable
     */
    private $headerFormatting;

    public function __construct(array $rows, string $headerColumn)
    {
        parent::__construct($rows);
        $this->setHeaderColumn($headerColumn);
        $this->headerFormatting = function ($value) {
            return $value;
        };
    }

    /**
     * Applies the given column as list header. Column must exist in result set, otherwise an exception is thrown.
     *
     * @param string $column
     */
    public function setHeaderColumn(string $column): void
    {
        $this->headerColumn = $column;
        $row = $this->getRow(0);
        if ($row && !property_exists($row, $this->headerColumn)) {
            throw new RuntimeException("The header column property [$this->headerColumn] must be defined in the result set.");
        }
    }

    /**
     * Sets the callback used to display the header value.
     *
     * @param callable $callback
     */
    public function setHeaderFormatting(callable $callback): void
    {
        $this->headerFormatting = $callback;
    }

    public function getRawRows(): array
    {
        return parent::getRows();
    }

    public function getRows(): array
    {
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