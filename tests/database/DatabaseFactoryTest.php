<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\DatabaseFactory;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseFactoryTest extends TestCase
{
    public function testMySql()
    {
        $this->expectException(DatabaseException::class);
        $database = DatabaseFactory::buildFromConfigurations(['dbms' => 'mysql']);
        self::assertEquals("mysql", $database->getAdapter()->getDatabaseManagementSystem());
    }

    public function testPostgres()
    {
        $this->expectException(DatabaseException::class);
        $database = DatabaseFactory::buildFromConfigurations(['dbms' => 'pgsql']);
        self::assertEquals("pgsql", $database->getAdapter()->getDatabaseManagementSystem());
    }

    public function testInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $database = DatabaseFactory::buildFromConfigurations([]);
    }
}
