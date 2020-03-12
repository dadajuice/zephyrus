<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\DatabaseFactory;

class DatabaseFactoryTest extends TestCase
{
    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testMySql()
    {
        $database = DatabaseFactory::buildFromConfigurations(['dbms' => 'mysql']);
        self::assertEquals("mysql", $database->getAdapter()->getDatabaseManagementSystem());
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testPostgres()
    {
        $database = DatabaseFactory::buildFromConfigurations(['dbms' => 'pgsql']);
        self::assertEquals("pgsql", $database->getAdapter()->getDatabaseManagementSystem());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalid()
    {
        $database = DatabaseFactory::buildFromConfigurations([]);
    }
}
