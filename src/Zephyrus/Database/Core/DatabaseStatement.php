<?php namespace Zephyrus\Database\Core;

use PDOStatement;
use stdClass;
use Zephyrus\Application\Configuration;

class DatabaseStatement
{
    const TYPE_INTEGER = ['LONGLONG', 'LONG', 'INTEGER', 'INT4'];
    const TYPE_BOOLEAN = ['TINY', 'BOOL'];
    const TYPE_FLOAT = ['NEWDECIMAL', 'FLOAT', 'DOUBLE', 'DECIMAL', 'NUMERIC'];

    /**
     * @var PDOStatement
     */
    private $statement = null;

    /**
     * @var array
     */
    private $fetchColumnTypes = [];

    /**
     * @var callable
     */
    private $sanitizeCallback = null;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->initializeTypeConversion();
    }

    /**
     * Returns the next row from the current result set obtained from the last
     * executed query. Automatically strip slashes that would have been stored
     * in database as escaping.
     *
     * @return stdClass|null
     */
    public function next(): ?stdClass
    {
        $row = $this->statement->fetch(\PDO::FETCH_OBJ);
        if ($row === false) {
            return null;
        }
        if (!empty($this->fetchColumnTypes)) {
            $this->convertRowTypes($row);
        }
        if (!is_null($this->sanitizeCallback)) {
            $this->sanitizeOutput($row);
        }
        return $row;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->statement->rowCount();
    }

    /**
     * Defines the function to be executed to sanitize any output from the Database responses.
     */
    public function setSanitizeCallback($callback)
    {
        $this->sanitizeCallback = $callback;
    }

    private function convertRowTypes(&$row)
    {
        foreach (get_object_vars($row) as $column => $value) {
            if (isset($this->fetchColumnTypes[$column]) && !is_null($value) && is_callable($this->fetchColumnTypes[$column])) {
                $row->{$column} = $this->fetchColumnTypes[$column]($row->{$column});
            }
        }
    }

    private function sanitizeOutput(&$row)
    {
        foreach (get_object_vars($row) as $column => $value) {
            if (is_string($value)) {
                $row->{$column} = ($this->sanitizeCallback)($value);
            }
        }
    }

    private function initializeTypeConversion()
    {
        for ($i = 0; $i < $this->statement->columnCount(); ++$i) {
            try {
                $meta = $this->statement->getColumnMeta($i);
                $this->fetchColumnTypes[$meta['name']] = $this->getMetaCallback(strtoupper($meta['native_type']));
            } catch (\Exception $exception) {
                // With DBMS SQLite, if a query has no result, it cannot use the getColumnMeta method as this will
                // throw an out of range exception even if the columnCount returns the correct result. Must be a bug
                // within PDO statement with SQLite. To avoid any problem, an empty catch will make sure to ignore
                // such error as anyway no conversion will be necessary with empty results.
            }
        }
    }

    private function getMetaCallback(string $pdoType): ?string
    {
        if (in_array($pdoType, self::TYPE_INTEGER)) {
            return "intval";
        }
        // Boolean type doesn't exist in SQLITE
        // @codeCoverageIgnoreStart
        if (in_array($pdoType, self::TYPE_BOOLEAN)) {
            return "boolval";
        }
        // @codeCoverageIgnoreEnd
        if (in_array($pdoType, self::TYPE_FLOAT)) {
            return "floatval";
        }
        return null;
    }
}
