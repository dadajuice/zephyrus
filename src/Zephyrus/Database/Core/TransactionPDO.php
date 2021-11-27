<?php namespace Zephyrus\Database\Core;

class TransactionPDO extends \PDO
{
    private static $savepointEnabled = ["pgsql", "mysql", "sqlite"];

    /**
     * @var int
     */
    private $currentTransactionLevel = 0;

    /**
     * PDO begin transaction override to work with savepoint capabilities for
     * supported SGBD. Allows nested transactions.
     */
    public function beginTransaction(): bool
    {
        if ($this->currentTransactionLevel == 0 || !$this->nestable()) {
            parent::beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL{$this->currentTransactionLevel}");
        }
        ++$this->currentTransactionLevel;
        return true;
    }

    /**
     * PDO commit override to work with savepoint capabilities for supported
     * SGBD. Allows nested transactions.
     */
    public function commit(): bool
    {
        --$this->currentTransactionLevel;
        if ($this->currentTransactionLevel == 0 || !$this->nestable()) {
            return parent::commit();
        } else {
            return $this->exec("RELEASE SAVEPOINT LEVEL{$this->currentTransactionLevel}");
        }
    }

    /**
     * PDO rollback override to work with savepoint capabilities for
     * supported SGBD. Allows nested transactions.
     */
    public function rollBack(): bool
    {
        --$this->currentTransactionLevel;
        if ($this->currentTransactionLevel == 0 || !$this->nestable()) {
            return parent::rollBack();
        } else {
            return $this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->currentTransactionLevel}");
        }
    }

    /**
     * Verifies if the current PDO driver supports save points.
     *
     * @return bool
     */
    private function nestable()
    {
        return in_array($this->getAttribute(\PDO::ATTR_DRIVER_NAME), self::$savepointEnabled);
    }
}
