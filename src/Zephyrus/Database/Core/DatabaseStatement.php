<?php namespace Zephyrus\Database\Core;

use Exception;
use PDO;
use PDOStatement;
use stdClass;

/**
 * This class serves as a PDOStatement wrapper with some specialized behavior such as automatically convert value types
 * and doing sanitization of string values. Each query using the Database facade class will produce a DatabaseStatement
 * which in turn can be used to advance in the result set.
 */
class DatabaseStatement
{
    public const TYPE_INTEGER = ['LONGLONG', 'LONG', 'INTEGER', 'INT4', 'INT8'];
    public const TYPE_BOOLEAN = ['TINY', 'BOOL'];
    public const TYPE_FLOAT = ['NEWDECIMAL', 'FLOAT', 'DOUBLE', 'DECIMAL', 'NUMERIC', 'FLOAT8'];

    private PDOStatement $statement;
    private array $fetchColumnTypes = [];

    /**
     * @var callable
     */
    private $sanitizeCallback = null;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
        //$this->statement->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL);
        $this->initializeTypeConversion();
    }

    /**
     * Returns the next row from the current result set (statement). Automatically strip slashes that would have been
     * stored in database as escaping.
     *
     * @return stdClass|null
     */
    public function next(): ?stdClass
    {
        return $this->prepareRow($this->statement->fetch(PDO::FETCH_OBJ));
    }

    /**
     * Counts the number of rows contain in the result set.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Defines the function to be executed to sanitize any output from the Database responses.
     *
     * @param callable $callback
     */
    public function setSanitizeCallback(callable $callback)
    {
        $this->sanitizeCallback = $callback;
    }

    /**
     * Retrieves the wrapped native PDO statement instance.
     *
     * @return PDOStatement
     */
    public function getPdoStatement(): PDOStatement
    {
        return $this->statement;
    }

    private function prepareRow(stdClass|bool $row): ?stdClass
    {
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
     * Converts the values of the given row to its native counterpart if available (e.g. int column should be extracted
     * as an int and not string which is the default behavior). Modifies the row directly.
     *
     * @param stdClass $row
     */
    private function convertRowTypes(stdClass $row)
    {
        foreach (get_object_vars($row) as $column => $value) {
            if (isset($this->fetchColumnTypes[$column])
                && !is_null($value)
                && is_callable($this->fetchColumnTypes[$column])) {
                $row->{$column} = $this->fetchColumnTypes[$column]($row->{$column});
            }
        }
    }

    /**
     * Sanitizes all string values with the configured sanitize callback. This should be generic security filtering and
     * not column specific. Modifies the row directly.
     *
     * @param stdClass $row
     */
    private function sanitizeOutput(stdClass $row)
    {
        foreach (get_object_vars($row) as $column => $value) {
            if (is_string($value)) {
                $row->{$column} = ($this->sanitizeCallback)($value);
            }
        }
    }

    /**
     * Prepares the information needed to process the value type conversion. This method analyse the native type of the
     * reset set columns and prepares the callback conversion function call accordingly.
     */
    private function initializeTypeConversion()
    {
        for ($i = 0; $i < $this->statement->columnCount(); ++$i) {
            try {
                $column = $this->statement->getColumnMeta($i);
                $this->fetchColumnTypes[$column['name']] = $this->getMetaCallback(strtoupper($column['native_type']));
            } catch (Exception) { // @codeCoverageIgnore
                // With DBMS SQLite, if a query has no result, it cannot use the getColumnMeta method as this will
                // throw an out of range exception even if the columnCount returns the correct result. Must be a bug
                // within PDO statement with SQLite. To avoid any problem, an empty catch will make sure to ignore
                // such error as anyway no conversion will be necessary with empty results.
            }
        }
    }

    /**
     * Gets the native PHP function name to convert a string to a native type (either int, float or boolean). Returns
     * NULL for string evaluated types (VARCHAR, TEXT, etc.).
     *
     * @param string $pdoType
     * @return string|null
     */
    private function getMetaCallback(string $pdoType): mixed
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
        if (str_starts_with($pdoType, "_")) {
            return function ($value) {
                $array = str_replace(["{", "}"], "", $value);
                return explode(",", $array);
            };
        }
        return null;
    }
}
