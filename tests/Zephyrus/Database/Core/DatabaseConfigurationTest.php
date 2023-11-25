<?php namespace Zephyrus\Tests\Database\Core;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\DatabaseConfiguration;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseConfigurationTest extends TestCase
{
    public function testDefaultConfigurations()
    {
        $source = new DatabaseConfiguration();
        self::assertEquals("", $source->getDatabaseName());
        self::assertEquals("localhost", $source->getHostname());
        self::assertEquals("pgsql:dbname=;host=localhost;", $source->getDatabaseSourceName());
    }

    public function testConfigIniConfigurations()
    {
        $source = new DatabaseConfiguration(Configuration::getDatabase());
        self::assertEquals("zephyrus", $source->getDatabaseName());
        self::assertEquals("zephyrus_database", $source->getHostname());
        self::assertEquals("pgsql:dbname=zephyrus;host=zephyrus_database;", $source->getDatabaseSourceName());
    }

    public function testManualConfigurations()
    {
        $source = new DatabaseConfiguration([
            'hostname' => '10.10.4.36',
            'port' => '888',
            'charset' => 'utf8',
            'database' => 'test',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
        self::assertEquals("test", $source->getDatabaseName());
        self::assertEquals("10.10.4.36", $source->getHostname());
        self::assertEquals("admin", $source->getUsername());
        self::assertEquals("Passw0rd123!", $source->getPassword());
        self::assertEquals("pgsql:dbname=test;host=10.10.4.36;port=888;", $source->getDatabaseSourceName());
    }

    public function testInvalidPort()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::INVALID_PORT_CONFIGURATION);
        new DatabaseConfiguration([
            'hostname' => '10.10.4.36',
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
        new DatabaseConfiguration([
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
        new DatabaseConfiguration([
            'hostname' => '10.10.4.36',
            'port' => '888',
            'charset' => 'utf8',
            'username' => 'admin',
            'password' => 'Passw0rd123!'
        ]);
    }
}
