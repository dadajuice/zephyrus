<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;

class ConfigurationTest extends TestCase
{
    public function testReadAllConfigurations()
    {
        $config = Configuration::getConfigurations();
        self::assertEquals('aes-256-cbc', $config['security']['encryption_algorithm']);
        self::assertEquals('localhost', $config['database']['host']);
    }

    public function testReadSingleConfiguration()
    {
        $config = Configuration::getConfiguration('application', 'env');
        self::assertEquals('dev', $config);
    }

    public function testReadSecurityConfiguration()
    {
        $config = Configuration::getSecurityConfiguration();
        $precise = (bool)Configuration::getSecurityConfiguration('ids_enabled');
        self::assertTrue($precise);
        self::assertEquals('aes-256-cbc', $config['encryption_algorithm']);
    }

    public function testReadDatabaseConfiguration()
    {
        $config = Configuration::getDatabaseConfiguration();
        $precise = Configuration::getDatabaseConfiguration('host');
        self::assertEquals('localhost', $precise);
        self::assertEquals('utf8', $config['charset']);
    }

    public function testReadSessionConfiguration()
    {
        $config = Configuration::getSessionConfiguration();
        $precise = (bool)Configuration::getSessionConfiguration('encryption_enabled');
        self::assertTrue($precise);
        self::assertEquals('60', $config['refresh_after_interval']);
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidSection()
    {
        Configuration::getConfiguration('invalid');
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidSectionField()
    {
        Configuration::getApplicationConfiguration('invalid');
    }
}