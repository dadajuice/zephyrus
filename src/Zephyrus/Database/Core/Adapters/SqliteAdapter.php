<?php namespace Zephyrus\Database\Core\Adapters;

class SqliteAdapter extends DatabaseAdapter
{
    const DBMS = ["sqlite", "sqlite2"];

    protected function buildDataSourceName(): string
    {
        $dsnPrefix = $this->getDatabaseManagementSystem() . ':';
        return $dsnPrefix . ((!empty($this->getDatabaseName())) ? $this->getDatabaseName() : ':memory:');
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
