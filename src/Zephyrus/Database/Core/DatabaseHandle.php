<?php namespace Zephyrus\Database\Core;

use PDO;

/**
 * Wrapper class for the PDO instance including nested transaction functionality known as SAVEPOINT. This is the main
 * instance wrapped in the Database class to execute queries.
 */
class DatabaseHandle extends PDO
{
    private int $currentTransactionLevel = 0;

    public function __construct(string $dsn, string|null $username, string|null $password)
    {
        parent::__construct($dsn, $username, $password);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * PDO begin transaction override to work with savepoint capabilities for supported DBMS. Allows for working nested
     * transactions.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        ($this->currentTransactionLevel == 0)
            ? parent::beginTransaction()
            : $this->exec("SAVEPOINT LEVEL$this->currentTransactionLevel");
        ++$this->currentTransactionLevel;
        return true;
    }

    /**
     * PDO commit override to work with savepoint capabilities for supported DBMS. Allows for working nested
     * transactions.
     *
     * @return bool
     */
    public function commit(): bool
    {
        --$this->currentTransactionLevel;
        if ($this->currentTransactionLevel <= 0) {
            $this->currentTransactionLevel = 0;
            return parent::commit();
        }
        $this->exec("RELEASE SAVEPOINT LEVEL$this->currentTransactionLevel");
        return true;
    }

    /**
     * PDO rollback override to work with savepoint capabilities for supported DBMS. Allows for working nested
     * transactions.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        --$this->currentTransactionLevel;
        if ($this->currentTransactionLevel <= 0) {
            $this->currentTransactionLevel = 0;
            return parent::rollBack();
        }
        $this->exec("ROLLBACK TO SAVEPOINT LEVEL$this->currentTransactionLevel");
        return true;
    }
}
