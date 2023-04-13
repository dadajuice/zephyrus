<?php namespace Zephyrus\Database\Core;

use PDO;
use PDOException;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Exceptions\FatalDatabaseException;

class Database
{
    private DatabaseConfiguration $configuration;
    private DatabaseHandle $handle;
    private SchemaInterrogator $schemaInterrogator;

    /**
     * Instantiates the database facade instance which will permit queries to be sent to the database.
     *
     * @param array $configurations
     * @throws FatalDatabaseException
     */
    public function __construct(array $configurations)
    {
        $this->configuration = new DatabaseConfiguration($configurations);
        $this->handle = $this->connect();
        $this->schemaInterrogator = new SchemaInterrogator($this);
    }

    /**
     * Executes a parametrized SQL query. Parameters must be included as an array compatible with the PDO query
     * preparation.
     *
     * @param string $query
     * @param array $parameters
     * @throws DatabaseException
     * @return DatabaseStatement
     */
    public function query(string $query, array $parameters = []): DatabaseStatement
    {
        try {
            $statement = $this->handle->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            foreach ($parameters as $name => &$variable) {
                $statement->bindParam(
                    is_string($name) ? ":$name" : intval($name) + 1,
                    $variable,
                    $this->evaluatePdoType($variable)
                );
            }
            $statement->execute();
            return new DatabaseStatement($statement);
        } catch (PDOException $e) {
            throw new DatabaseException('Error while preparing query « ' . $query . ' » (' .
                $e->getMessage() . ')', $query);
        }
    }

    /**
     * Retrieves the configured database source currently used by the instance.
     *
     * @return DatabaseConfiguration
     */
    final public function getConfiguration(): DatabaseConfiguration
    {
        return $this->configuration;
    }

    /**
     * Proceeds to include the given variable into the database environnement so that the executed queries, triggers or
     * stored procedures could have access to the variable. Useful for example to pass a user id to register for
     * automated log triggers.
     *
     * @param string $name
     * @param string $value
     */
    public function addSessionVariable(string $name, string $value)
    {
        $this->query("set session \"$name\" = '$value';");
    }

    /**
     * Retrieves the interrogator instance to perform various meta database (schema) queries.
     *
     * @return SchemaInterrogator
     */
    public function getSchemaInterrogator(): SchemaInterrogator
    {
        return $this->schemaInterrogator;
    }

    /**
     * Retrieves the wrapped native PDO instance used for database interaction.
     *
     * @return DatabaseHandle
     */
    public function getHandle(): DatabaseHandle
    {
        return $this->handle;
    }

    /**
     * Disable auto-commit mode and begin an SQL transaction. When this method is called, database will only be updated
     * when calling the <commit> method. Calling the <rollback> method will undo any SQL commands done within the
     * started transaction.
     *
     * @see self::commit()
     * @see self::rollback()
     */
    public function beginTransaction()
    {
        $this->handle->beginTransaction();
    }

    /**
     * Manually commit a started SQL transaction. After a successful commit, the connection handler will return
     * auto-commit mode.
     *
     * @throws FatalDatabaseException
     */
    public function commit()
    {
        try {
            $this->handle->commit();
        } catch (PDOException $e) {
            throw FatalDatabaseException::transactionCommitFailed($e->getMessage());
        }
    }

    /**
     * Cancels any SQL commands done within a started SQL transaction. After a successful rollback, the connection
     * handler will return auto-commit mode.
     *
     * @throws FatalDatabaseException
     */
    public function rollback()
    {
        try {
            $this->handle->rollBack();
        } catch (PDOException $e) {
            throw FatalDatabaseException::transactionRollbackFailed($e->getMessage());
        }
    }

    /**
     * Finds the last inserted id for the specified sequence name.
     *
     * @param string $sequenceName
     * @return string
     */
    public function getLastInsertedId(string $sequenceName): string
    {
        return $this->handle->lastInsertId($sequenceName);
    }

    public function getVersion(): string
    {
        $statement = $this->query("SHOW server_version");
        $row = $statement->next();
        return $row->server_version;
    }

    public function getSize(): int
    {
        $statement = $this->query("SELECT pg_database_size('" . $this->configuration->getDatabaseName() . "') as size");
        $row = $statement->next();
        return $row->size;
    }

    /**
     * Creates the PDO handle to allow for query to be executed to the configured database source. Will throw
     * a FatalDatabaseException when connection fails.
     *
     * @throws FatalDatabaseException
     * @return DatabaseHandle
     */
    private function connect(): DatabaseHandle
    {
        try {
            return new DatabaseHandle(
                $this->configuration->getDatabaseSourceName(),
                $this->configuration->getUsername(),
                $this->configuration->getPassword()
            );
        } catch (PDOException $e) {
            throw FatalDatabaseException::connectionFailed($e->getMessage());
        }
    }

    /**
     * Guesses the best PDO::PARAM_x type constant for a given variable. Ignored from coverage because test Database
     * sqlite doesn't have proper BOOL or NULL.
     *
     * @codeCoverageIgnore
     * @param mixed $variable
     * @return int
     */
    private function evaluatePdoType(mixed $variable): int
    {
        if (is_float($variable)) {
            // PDO doesn't have PARAM_FLOAT, so it must be evaluated as STR
            return PDO::PARAM_STR;
        } elseif (is_int($variable)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($variable)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($variable)) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
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
        return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML401);
    }
}
