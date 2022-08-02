<?php namespace Zephyrus\Tests\Database\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Adapters\Mysql\MysqlAdapter;
use Zephyrus\Database\Core\Adapters\Postgresql\PostgresAdapter;
use Zephyrus\Database\Core\Adapters\Sqlite\SqliteAdapter;
use Zephyrus\Database\Core\DatabaseConfiguration;
use Zephyrus\Database\QueryBuilder\LimitClause;

class LimitClauseTest extends TestCase
{
    public function testLimit()
    {
        $limit = new LimitClause(50);
        self::assertEquals("LIMIT 50", $limit->getSql(new SqliteAdapter(new DatabaseConfiguration())));
    }

    public function testLimitWithOffsetSqlite()
    {
        $limit = new LimitClause(50, 10);
        self::assertEquals("LIMIT 10, 50", $limit->getSql(new SqliteAdapter(new DatabaseConfiguration())));
    }

    public function testLimitWithOffsetMysql()
    {
        $limit = new LimitClause(50, 10);
        self::assertEquals("LIMIT 10, 50", $limit->getSql(new MysqlAdapter(new DatabaseConfiguration())));
    }

    public function testLimitWithOffsetPostgres()
    {
        $limit = new LimitClause(50, 10);
        self::assertEquals("LIMIT 50 OFFSET 10", $limit->getSql(new PostgresAdapter(new DatabaseConfiguration())));
    }
}
