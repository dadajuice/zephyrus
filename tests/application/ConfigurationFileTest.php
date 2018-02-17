<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ConfigurationFile;

class ConfigurationFileTest extends TestCase
{
    public function testLoad()
    {
        $ini = new ConfigurationFile(ROOT_DIR . '/config.ini');
        $data = $ini->read();
        self::assertTrue(array_key_exists('database', $data));
        $data = $ini->read('database');
        self::assertTrue(array_key_exists('host', $data));
        $data = $ini->read('database', 'host');
        self::assertEquals('localhost', $data);
    }

    public function testWrite()
    {
        $ini = new ConfigurationFile(ROOT_DIR . '/test.ini');
        $ini->write([
            'test' => [
                'propertyA' => 1,
                'propertyB' => 2
            ],
            'test2' => 3
        ]);
        $data = $ini->read();
        self::assertTrue(array_key_exists('test', $data));
        $data = $ini->read('test');
        self::assertTrue(array_key_exists('propertyA', $data));
        $data = $ini->read('test', 'propertyA');
        self::assertEquals(1, $data);
        $ini->writeSection('test3', [
            'Junji' => 'Ito'
        ]);
        self::assertEquals("Ito", $ini->read('test3', 'Junji'));
    }

    public function testSave()
    {
        $ini = new ConfigurationFile(ROOT_DIR . '/test.ini');
        $ini->write([
            'test' => [
                'propertyA' => 1,
                'propertyB' => 2,
                'propertyC' => ['b', 'd'],
                'propertyD' => [
                    'a' => 'b',
                    'b' => 'd'
                ],
            ],
            'test2' => 3
        ]);
        $ini->save();
        $ini = new ConfigurationFile(ROOT_DIR . '/test.ini');
        $data = $ini->read('test', 'propertyA');
        self::assertEquals(1, $data);
        @unlink(ROOT_DIR . '/test.ini');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLock()
    {
        $ini = new ConfigurationFile('/etc/lock.ini');
        $ini->write([
            'test' => 5
        ]);
        $ini->save();
    }
}
