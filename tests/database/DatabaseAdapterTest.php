<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Database\Core\Adapters\MysqlAdapter;
use Zephyrus\Database\Core\Adapters\PostgresqlAdapter;
use Zephyrus\Database\Core\Adapters\SqliteAdapter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseAdapterTest extends TestCase
{
    public function testAllConfigurationGetters()
    {
        $adapter = new MysqlAdapter([
            'dbms' => 'mysql',
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test',
            'username' => 'martin',
            'password' => 'sandwish',
            'charset' => 'UTF-8'
        ]);
        self::assertEquals('3306', $adapter->getPort());
        self::assertEquals('localhost', $adapter->getHost());
        self::assertEquals('UTF-8', $adapter->getCharset());
        self::assertEquals('martin', $adapter->getUsername());
        self::assertEquals('sandwish', $adapter->getPassword());
        self::assertEquals('test', $adapter->getDatabaseName());
        self::assertEquals('mysql', $adapter->getDatabaseManagementSystem());
        self::assertEquals('mysql:dbname=test;host=localhost;port=3306;charset=UTF-8;', $adapter->getDataSourceName());
        self::assertEquals("SET @bob = 'lewis'", $adapter->getAddEnvironmentVariableClause('bob', 'lewis'));
    }

    public function testMemorySqlite()
    {
        $adapter = new SqliteAdapter([
            'dbms' => 'sqlite'
        ]);
        self::assertEquals("", $adapter->getAddEnvironmentVariableClause('bob', 'lewis'));
        $adapter = new class(['dbms' => 'mysql']) extends DatabaseAdapter
        {

        };
        self::assertEquals("SET @bob = 'lewis'", $adapter->getAddEnvironmentVariableClause('bob', 'lewis'));
    }

    public function testFileSqlite()
    {
        $adapter = new SqliteAdapter([
            'dbms' => 'mysql',
            'database' => ROOT_DIR . '/lib/db.sqlite'
        ]);
        self::assertEquals(ROOT_DIR . '/lib/db.sqlite', $adapter->getDatabaseName());
    }

    public function testFileSqliteError()
    {
        $this->expectException(DatabaseException::class);
        $adapter = new SqliteAdapter([
            'dbms' => 'mysql',
            'database' => ROOT_DIR . '/lib/db.sqlidfljsdfkjlsdkfjlte'
        ]);
        $database = new Database($adapter);
    }

    public function testPgsql()
    {
        $adapter = new PostgresqlAdapter([
            'dbms' => 'pgsql'
        ]);
        self::assertEquals("set session \"bob\" = 'lewis';", $adapter->getAddEnvironmentVariableClause('bob', 'lewis'));
        self::assertEquals("(test ILIKE '%bob%')", $adapter->getSearchFieldClause('test', 'bob'));
        self::assertEquals("(\"test\" ILIKE '%bob%')", $adapter->getSearchFieldClause('"test"', 'bob'));
        self::assertEquals(" LIMIT 50 OFFSET 4", $adapter->getLimitClause(4, 50));
    }

    public function testInvalidDbms()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MysqlAdapter(['test' => 'kjdshfkhdsf']);
    }

    public function testDriverNotInstalled()
    {
        $this->expectException(DatabaseException::class);
        $adapter = new MysqlAdapter(['dbms' => 'batman']);
        $adapter->buildHandle();
    }

    public function testConnectionFailed()
    {
        $this->expectException(DatabaseException::class);
        $adapter = new MysqlAdapter(['dbms' => 'mysql', 'host' => 'localhost', 'port' => '9999', 'username' => 'bob', 'password' => 'bubu', 'database' => 'jksdhfkjhsdf']);
        $adapter->buildHandle();
    }
}
