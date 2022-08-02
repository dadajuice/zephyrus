<?php namespace Zephyrus\Database\Core;

use PDO;
use PDOException;
use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Exceptions\FatalDatabaseException;

class Database
{
    private DatabaseHandle $handle;
    private DatabaseAdapter $adapter;
    private SchemaInterrogator $schemaInterrogator;

    /**
     * Instantiates the database facade instance which will permit queries to be sent to the database.
     *
     * @param DatabaseConfiguration $source
     * @throws FatalDatabaseException
     */
    public function __construct(DatabaseConfiguration $source)
    {
        $this->adapter = DatabaseAdapter::build($source);
        $this->handle = $this->adapter->connect();
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
                    (is_string($name) ? ":$name" : intval($name) + 1),
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
     * Adds a variable to the database session that shall become available for following queries / triggers / store
     * procedures and functions.
     *
     * @param string $name
     * @param string $value
     */
    public function addSessionVariable(string $name, string $value)
    {
        $sql = $this->adapter->getSqlAddVariable($name, $value);
        if (empty($sql)) {
            return; // Guard for non existent session environnement feature (e.g. sqlite).
        }
        $this->query($sql);
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
     * Retrieves the configured database source currently used by the instance.
     *
     * @return DatabaseConfiguration
     */
    public function getSource(): DatabaseConfiguration
    {
        return $this->adapter->getSource();
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
     * Retrieves the configured database adapter based on the DBMS.
     *
     * @return DatabaseAdapter
     */
    public function getAdapter(): DatabaseAdapter
    {
        return $this->adapter;
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
     * @param string|null $name
     * @return string
     */
    public function getLastInsertedId(string $name = null): string
    {
        return $this->handle->lastInsertId($name);
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
}
