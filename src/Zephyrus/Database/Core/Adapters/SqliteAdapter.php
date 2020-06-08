<?php namespace Zephyrus\Database\Core\Adapters;

use Zephyrus\Exceptions\DatabaseException;

class SqliteAdapter extends DatabaseAdapter
{
    const DBMS = ["sqlite", "sqlite2"];

    public function buildHandle(): \PDO
    {
        if (!empty($this->getDatabaseName())) {
            $path = ROOT_DIR . DIRECTORY_SEPARATOR . $this->getDatabaseName();
            if (!file_exists($path)) {
                throw new DatabaseException("The specified SQLite database file [$path] doesn't exists");
            }
        }
        return parent::buildHandle();
    }

    protected function buildDataSourceName(): string
    {
        $dsnPrefix = $this->getDatabaseManagementSystem() . ':';
        return $dsnPrefix . ((!empty($this->getDatabaseName()))
                ? ROOT_DIR . DIRECTORY_SEPARATOR . $this->getDatabaseName()
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
}
