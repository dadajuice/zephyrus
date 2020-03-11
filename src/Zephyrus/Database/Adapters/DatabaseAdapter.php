<?php namespace Zephyrus\Database\Adapters;

use PDO;
use Zephyrus\Database\Database;
use Zephyrus\Exceptions\DatabaseException;

abstract class DatabaseAdapter
{
    /**
     * @var string
     */
    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function searchPattern(string $field, string $search): string
    {
        $field = $this->purify($field);
        $search = $this->purify($search);
        return "($field LIKE %$search%)";
    }

    public function getLimitClause(int $offset, int $maxEntities): string
    {
        return " LIMIT $offset, $maxEntities";
    }

    public function addSessionVariable(string $name, string $value)
    {
        $this->database->query("SET @$name = ?", [$value]);
    }

    public function countAll(string $table)
    {
        return "SELECT COUNT(*) FROM $table";
    }

    public function getDriverName(): string
    {
        return $this->database->getDatabaseManagementSystem();
    }

    /**
     * Basic filtering to eliminate any tags and empty leading / trailing
     * characters.
     *
     * @param string $data
     * @return string
     */
    public function purify($data): string
    {
        return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES | ENT_HTML401, 'UTF-8');
    }
}
