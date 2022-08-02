<?php namespace Zephyrus\Database\Core\Adapters\Sqlite;

use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseHandle;
use Zephyrus\Exceptions\FatalDatabaseException;

class SqliteAdapter extends DatabaseAdapter
{
    /**
     * Overrides the default buildHandle method to verify the existence of the database file if it was specified in the
     * configurations.
     *
     * @return DatabaseHandle
     * @throws FatalDatabaseException
     */
    public function buildConnector(): DatabaseHandle
    {
        if ($this->source->getDatabaseName() != ":memory:") {
            $path = ROOT_DIR . DIRECTORY_SEPARATOR . $this->source->getDatabaseName();
            if (!file_exists($path) || !is_readable($path)) {
                throw FatalDatabaseException::sqliteInvalidDatabase($path);
            }
        }
        return parent::buildConnector();
    }

    /**
     * Overrides the data source name builder to simplify for SQLite usage in relation to the database file path.
     *
     * @return string
     */
    public function getDsn(): string
    {
        $dsnPrefix = $this->source->getDatabaseManagementSystem() . ':';
        return $dsnPrefix . (($this->source->getDatabaseName() != ":memory:")
                ? ROOT_DIR . DIRECTORY_SEPARATOR . $this->source->getDatabaseName()
                : ':memory:');
    }

    public function getLikeClause(): string
    {
        return "LIKE";
    }


    /**
     * Non-existing feature in SQLite database.
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "";
    }

    public function buildSchemaInterrogator(Database $database): SqliteSchemaInterrogator
    {
        return new SqliteSchemaInterrogator($database);
    }

    public function getSqlLimit(int $limit, ?int $offset = null): string
    {
        if (!is_null($offset)) {
            return "LIMIT $offset, $limit";
        }
        return "LIMIT $limit";
    }
}
