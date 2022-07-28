<?php namespace Zephyrus\Database\Core\Adapters\Sqlite;

use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseConnector;
use Zephyrus\Exceptions\FatalDatabaseException;

class SqliteAdapter extends DatabaseAdapter
{
    /**
     * Overrides the default buildHandle method to verify the existence of the database file if it was specified in the
     * configurations.
     *
     * @return DatabaseConnector
     * @throws FatalDatabaseException
     */
    public function buildConnector(): DatabaseConnector
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
    protected function buildDataSourceName(): string
    {
        $dsnPrefix = $this->source->getDatabaseManagementSystem() . ':';
        return $dsnPrefix . (($this->source->getDatabaseName() != ":memory:")
                ? ROOT_DIR . DIRECTORY_SEPARATOR . $this->source->getDatabaseName()
                : ':memory:');
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

    public function getLimitClause(int $limit, int $offset): string
    {
        return " LIMIT $offset, $limit";
    }
}
