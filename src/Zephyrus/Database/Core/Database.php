<?php namespace Zephyrus\Database\Core;

use PDO;
use PDOException;
use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Exceptions\DatabaseException;

class Database
{
    /**
     * @var \PDO
     */
    private $handle = null;

    /**
     * @var DatabaseAdapter
     */
    private $adapter;

    /**
     * @param DatabaseAdapter $adapter
     * @throws DatabaseException
     */
    public function __construct(DatabaseAdapter $adapter)
    {
        $this->adapter = $adapter;
        $this->handle = $adapter->buildHandle();
    }

    /**
     * Execute a parametrized SQL query. Parameters must be included as an
     * array compatible with the PDO query preparation.
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
     * @return DatabaseAdapter
     */
    public function getAdapter(): DatabaseAdapter
    {
        return $this->adapter;
    }

    /**
     * Disable auto-commit mode and begin an SQL transaction. When this method
     * is called, database will only be updated when calling the <commit>
     * method. Calling the <rollback> method will undo any SQL commands done
     * within the started transaction.
     *
     * @see self::commit()
     * @see self::rollback()
     */
    public function beginTransaction()
    {
        $this->handle->beginTransaction();
    }

    /**
     * Manually commit a started SQL transaction. After a successful commit, the
     * connection handler will return auto-commit mode.
     *
     * @throws DatabaseException
     */
    public function commit()
    {
        try {
            $this->handle->commit();
        } catch (PDOException $e) {
            throw new DatabaseException("Couldn't commit SQL transaction. Are you sure a transaction 
                has been started ?");
        }
    }

    /**
     * Cancel any SQL commands done within a started SQL transaction. After a
     * successful rollback, the connection handler will return auto-commit
     * mode.
     *
     * @throws DatabaseException
     */
    public function rollback()
    {
        try {
            $this->handle->rollBack();
        } catch (PDOException $e) {
            throw new DatabaseException("Couldn't rollback SQL transaction. Are you sure a transaction 
                has been started ?");
        }
    }

    /**
     * @param string|null $name
     * @return string
     */
    public function getLastInsertedId(string $name = null)
    {
        return $this->handle->lastInsertId($name);
    }

    /**
     * Guesses the best PDO::PARAM_x type constant for a given variable. Ignored
     * from coverage because test Database (sqlite) doesn't have proper BOOL or
     * NULL.
     *
     * @codeCoverageIgnore
     * @param mixed $variable
     * @return int
     */
    private function evaluatePdoType($variable): int
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
