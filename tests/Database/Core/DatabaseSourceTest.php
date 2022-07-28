<?php namespace Zephyrus\Tests\Database\Core;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\DatabaseSource;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseSourceTest extends TestCase
{
    public function testDefaultConfigurations()
    {
        $source = new DatabaseSource();
        self::assertEquals("sqlite", $source->getDatabaseManagementSystem());
        self::assertEquals(":memory:", $source->getDatabaseName());
        self::assertEquals("localhost", $source->getHost());
        self::assertEquals("sqlite:dbname=:memory:;host=localhost;", $source->getDatabaseSourceName());
    }

    public function testConfigIniConfigurations()
    {
        $source = new DatabaseSource(Configuration::getDatabaseConfiguration());
        self::assertEquals("sqlite", $source->getDatabaseManagementSystem());
        self::assertEquals("", $source->getDatabaseName());
        self::assertEquals("localhost", $source->getHost());
        self::assertEquals("sqlite:dbname=;host=localhost;", $source->getDatabaseSourceName());
    }

    public function testManualConfigurations()
    {
        $source = new DatabaseSource([
            'dbms' => 'pgsql',
            'host' => '10.10.4.36',
            'port' => '888',
            'charset' => 'utf8',
            'database' => 'test',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
        self::assertEquals("pgsql", $source->getDatabaseManagementSystem());
        self::assertEquals("test", $source->getDatabaseName());
        self::assertEquals("10.10.4.36", $source->getHost());
        self::assertEquals("admin", $source->getUsername());
        self::assertEquals("Passw0rd123!", $source->getPassword());
        self::assertEquals("utf8", $source->getCharset());
        self::assertEquals("pgsql:dbname=test;host=10.10.4.36;port=888;", $source->getDatabaseSourceName());
    }

    public function testInvalidPort()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::INVALID_PORT_CONFIGURATION);
        new DatabaseSource([
            'dbms' => 'pgsql',
            'host' => '10.10.4.36',
            'port' => 'dsfsdf',
            'charset' => 'utf8',
            'database' => 'test',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
    }

    public function testMissingHost()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::MISSING_CONFIGURATION);
        new DatabaseSource([
            'dbms' => 'pgsql',
            'port' => '888',
            'charset' => 'utf8',
            'database' => 'test',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
    }

    public function testMissingDatabase()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::MISSING_CONFIGURATION);
        new DatabaseSource([
            'dbms' => 'pgsql',
            'host' => '10.10.4.36',
            'port' => '888',
            'charset' => 'utf8',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
    }

    public function testMissingDbms()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::MISSING_CONFIGURATION);
        new DatabaseSource([
            'host' => '10.10.4.36',
            'port' => '888',
            'database' => 'test',
            'charset' => 'utf8',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
    }

    public function testUnavailableDbms()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::DRIVER_NOT_AVAILABLE);
        new DatabaseSource([
            'dbms' => 'ibm-db2',
            'host' => '10.10.4.36',
            'port' => '888',
            'database' => 'test',
            'charset' => 'utf8',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
    }
}
