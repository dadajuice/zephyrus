<?php namespace Zephyrus\Database\Core\Adapters;

use Zephyrus\Exceptions\DatabaseException;

class SqliteAdapter extends DatabaseAdapter
{
    const DBMS = ["sqlite", "sqlite2"];

    /**
     * Needs keys : dbms
     * Optional Key : database (absolute filepath to SQLite file else :memory: will be used)
     *
     * @param array $configurations
     * @throws DatabaseException
     */
    public function __construct(array $configurations)
    {
        parent::__construct($configurations);
        if (!empty($this->getDatabaseName())) {
            if (!file_exists($this->getDatabaseName())) {
                throw new DatabaseException("The given database file [{$this->getDatabaseName()}] for 
                    the SQLite instance doesn't exists.");
            }
            if (!is_readable($this->getDatabaseName())) {
                throw new DatabaseException("The given database file [{$this->getDatabaseName()}] for 
                    the SQLite instance is not readable.");
            }
            if (!is_writable($this->getDatabaseName())) {
                throw new DatabaseException("The given database file [{$this->getDatabaseName()}] for 
                    the SQLite instance is not writable.");
            }
        }
    }

    protected function buildDataSourceName(): string
    {
        $dsnPrefix = $this->getDatabaseManagementSystem() . ':';
        return $dsnPrefix . ((!empty($this->getDatabaseName())) ? $this->getDatabaseName() : ':memory:');
    }
}
