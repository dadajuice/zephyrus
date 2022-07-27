<?php namespace Zephyrus\Database\Core\Adapters;

use PDO;
use PDOException;
use Zephyrus\Database\Core\Adapters\Mysql\MysqlAdapter;
use Zephyrus\Database\Core\Adapters\Postgresql\PostgresAdapter;
use Zephyrus\Database\Core\Adapters\Sqlite\SqliteAdapter;
use Zephyrus\Database\Core\DatabaseConnector;
use Zephyrus\Database\Core\DatabaseSource;
use Zephyrus\Exceptions\FatalDatabaseException;

abstract class DatabaseAdapter
{
    protected DatabaseSource $source;
    private SchemaInterrogator $schemaInterrogator;

    /**
     * Builds the proper DatabaseAdapter instance based on the given database source. Cannot fail as the source is
     * verified beforehand.
     *
     * @param DatabaseSource $source
     * @return DatabaseAdapter
     */
    public static function build(DatabaseSource $source): DatabaseAdapter
    {
        return match ($source->getDatabaseSourceName()) {
            'sqlite', 'sqlite2' => new SqliteAdapter($source),
            'pgsql' => new PostgresAdapter($source),
            'mysql', 'mariadb' => new MysqlAdapter($source),
        };
    }

    /**
     * Creates the PDO handle to allow for query to be executed to the configured database source. This can be
     * overridden if a specific driver requires additional verifications (e.g. sqlite) or more attributes. Will throw
     * a FatalDatabaseException when connection fails.
     *
     * @return DatabaseConnector
     * @throws FatalDatabaseException
     */
    public function buildConnector(): DatabaseConnector
    {
        try {
            $handle = new DatabaseConnector($this->buildDataSourceName(), $this->source->getUsername(),
                $this->source->getPassword());
            $handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $handle;
        } catch (PDOException $e) {
            throw FatalDatabaseException::connectionFailed($e->getMessage());
        }
    }

    /**
     * Retrieves the configured database source instance for the adapter.
     *
     * @return DatabaseSource
     */
    final public function getSource(): DatabaseSource
    {
        return $this->source;
    }

    /**
     * Allows for overrides if a specific DBMS needs to build the PDO compatible DSN differently. E.g. MySQL supports
     * the addition of charset in its DSN and SQLite requires a simpler string.
     *
     * @return string
     */
    protected function buildDataSourceName(): string
    {
        return $this->source->getDatabaseSourceName();
    }

    /**
     * Builds the Adapter instance only accessible from the static build method. Cannot be instantiated otherwise to
     * assure compatibility.
     *
     * @param DatabaseSource $source
     */
    private function __construct(DatabaseSource $source)
    {
        $this->source = $source;
        $this->schemaInterrogator = $this->buildSchemaInterrogator();
    }






































    /**
     * Builds the correct SQL clause to send environnement variable to the database instance based on the given variable
     * name and variable value. Must be defined by the children classes.
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    public abstract function getAddEnvironmentVariableClause(string $name, string $value): string;

    public abstract function getLimitClause(int $limit, int $offset): string;

    public abstract function buildSchemaInterrogator(): SchemaInterrogator;

    // TODO: Querybuilder class ??





    public function getSchemaInterrogator(): SchemaInterrogator
    {
        return $this->schemaInterrogator;
    }

    /**
     * Basic filtering to eliminate any tags and empty leading / trailing
     * characters.
     *
     * @param string $data
     * @return string
     */
    public function purify(string $data): string
    {
        return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML401, 'UTF-8');
    }
}
