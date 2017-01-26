<?php namespace Zephyrus\Database;

use Zephyrus\Exceptions\DatabaseException;
use PDO;

class Database
{
    /**
     * @var TransactionPDO
     */
    private $handle = null;

    /**
     * Execute a parametrized SQL query. Parameters must be included as an
     * array compatible with the PDO query preparation.
     *
     * @param string $query
     * @param array $parameters
     * @return DatabaseStatement
     * @throws DatabaseException
     */
    public function query($query, $parameters = [])
    {
        try {
            $statement = $this->handle->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $statement->execute($parameters);
            return new DatabaseStatement($statement);
        } catch (\PDOException $e) {
            throw new DatabaseException('Error while preparing query « ' . $query . ' » (' .
                $e->getMessage() . ')', $query);
        }
    }

    /**
     * Disable auto-commit mode and begin an SQL transaction. When this method
     * is called, database will only be updated when calling the <commit>
     * method. Calling the <rollback> method will undo any SQL commands done
     * within the started transaction.
     *
     * @see self::commit()
     * @see self::rollback()
     * @throws DatabaseException
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
        } catch (\PDOException $e) {
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
        } catch (\PDOException $e) {
            throw new DatabaseException("Couldn't rollback SQL transaction. Are you sure a transaction 
                has been started ?");
        }
    }

    /**
     * @return string
     */
    public function getLastInsertedId()
    {
        return $this->handle->lastInsertId();
    }

    public function __construct(string $dsn, string $username = "", string $password = "")
    {
        try {
            $this->handle = new TransactionPDO($dsn, $username, $password);
            $this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new DatabaseException("Connection failed to database : " . $e->getMessage());
        }
    }
}
