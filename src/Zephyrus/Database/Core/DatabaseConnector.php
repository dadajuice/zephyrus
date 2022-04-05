<?php namespace Zephyrus\Database\Core;

use PDO;

class DatabaseConnector extends PDO
{
    private static array $savepointEnabled = ["pgsql", "mysql", "sqlite"];

    private int $currentTransactionLevel = 0;

    /**
     * PDO begin transaction override to work with savepoint capabilities for supported SGBD. Allows for working nested
     * transactions.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if ($this->currentTransactionLevel == 0 || !$this->nestable()) {
            parent::beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL$this->currentTransactionLevel");
        }
        ++$this->currentTransactionLevel;
        return true;
    }

    /**
     * PDO commit override to work with savepoint capabilities for supported SGBD. Allows for working nested
     * transactions.
     *
     * @return bool
     */
    public function commit(): bool
    {
        --$this->currentTransactionLevel;
        if ($this->currentTransactionLevel == 0 || !$this->nestable()) {
            return parent::commit();
        } else {
            $this->exec("RELEASE SAVEPOINT LEVEL$this->currentTransactionLevel");
        }
        return true;
    }

    /**
     * PDO rollback override to work with savepoint capabilities for supported SGBD. Allows for working nested
     * transactions.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        --$this->currentTransactionLevel;
        if ($this->currentTransactionLevel == 0 || !$this->nestable()) {
            return parent::rollBack();
        } else {
            $this->exec("ROLLBACK TO SAVEPOINT LEVEL$this->currentTransactionLevel");
        }
        return true;
    }

    /**
     * Verifies if the current PDO driver supports save points.
     *
     * @return bool
     */
    private function nestable(): bool
    {
        return in_array($this->getAttribute(PDO::ATTR_DRIVER_NAME), self::$savepointEnabled);
    }
}
