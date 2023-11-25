<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;

class ConfigurationTest extends TestCase
{
    public function testReadAllConfigurations()
    {
        Configuration::write(['database' => ['host' => 'localhost'], 'security' => ['encryption_algorithm' => 'aes-256-cbc']]);
        $config = Configuration::read();
        self::assertEquals('aes-256-cbc', $config['security']['encryption_algorithm']);
        self::assertEquals('localhost', $config['database']['host']);
        Configuration::write(null);
        $config = Configuration::read();
        self::assertEquals('zephyrus_database', $config['database']['hostname']);
        Configuration::write(null);
    }

    public function testFile()
    {
        Configuration::write(null);
        self::assertNull(Configuration::getFile());
    }

    public function testReadSingleConfiguration()
    {
        Configuration::write(['application' => ['env' => 'dev']]);
        $config = Configuration::getApplication('env', 'dev');
        self::assertEquals('dev', $config);
        Configuration::write(null);
    }

    public function testReadLocaleConfiguration()
    {
        Configuration::write(['locale' => ['currency' => 'CAD', 'charset' => 'utf8']]);
        $config = Configuration::getLocale();
        $precise = Configuration::getLocale('currency');
        self::assertEquals("CAD", $precise);
        self::assertEquals('utf8', $config['charset']);
        Configuration::write(null);
    }

    public function testReadDatabaseConfiguration()
    {
        Configuration::write(['database' => ['host' => 'localhost', 'charset' => 'utf8']]);
        $config = Configuration::getDatabase();
        $precise = Configuration::getDatabase('host');
        self::assertEquals('localhost', $precise);
        self::assertEquals('utf8', $config['charset']);
        Configuration::write(null);
    }

    public function testReadSessionConfiguration()
    {
        Configuration::write(['session' => ['encryption_enabled' => true, 'refresh_after_interval' => 60]]);
        $config = Configuration::getSession();
        $precise = (bool) Configuration::getSession('encryption_enabled');
        self::assertTrue($precise);
        self::assertEquals('60', $config['refresh_after_interval']);
        Configuration::write(null);
    }

    public function testInvalidConfigurationFile()
    {
        rename(ROOT_DIR . '/config.yml', ROOT_DIR . '/config.yml_test');
        $catch = false;
        try {
            Configuration::write(null);
            Configuration::read('session');
        } catch (\RuntimeException $e) {
            $catch = true;
        } finally {
            rename(ROOT_DIR . '/config.yml_test', ROOT_DIR . '/config.yml');
        }
        self::assertTrue($catch);
    }

    public function testInvalidSection()
    {
        $result = Configuration::read('invalid');
        self::assertNull($result);
        $result = Configuration::read('invalid', 'test');
        self::assertEquals('test', $result);
    }

    public function testInvalidSectionField()
    {
        $result = Configuration::getApplication('invalid');
        self::assertNull($result);
    }
}