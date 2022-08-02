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

    public function getSqlLimit(int $limit, ?int $offset = null): string
    {
        if (!is_null($offset)) {
            return "LIMIT $offset, $limit";
        }
        return "LIMIT $limit";
    }

    public function getSqlAddVariable(string $name, string $value): string
    {
        return "SET @$name = '$value'";
    }

    public function buildSchemaInterrogator(Database $database): SchemaInterrogator
    {
        return new MysqlSchemaInterrogator($database);
    }
}
