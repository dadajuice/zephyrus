<?php namespace Zephyrus\Database\Core\Adapters\Mysql;

use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Database\Core\Adapters\SchemaInterrogator;
use Zephyrus\Database\Core\Database;

class MysqlAdapter extends DatabaseAdapter
{
    /**
     * For MySql / MariaDB the charset can be specified in the DSN.
     *
     * @return string
     */
    public function getDsn(): string
    {
        $charset = (!empty($this->source->getCharset())) ? "charset={$this->source->getCharset()};" : "";
        return parent::getDsn() . $charset;
    }

    public function getLimitClause(int $limit, int $offset): string
    {
        return " LIMIT $offset, $limit";
    }

    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "SET @$name = '$value'";
    }

    public function buildSchemaInterrogator(Database $database): SchemaInterrogator
    {
        return new MysqlSchemaInterrogator($database);
    }
}
