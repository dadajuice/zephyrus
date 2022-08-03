<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ConfigurationFile;

class ConfigurationFileTest extends TestCase
{
    public function testLoad()
    {
        $ini = new ConfigurationFile(ROOT_DIR . '/config.ini');
        $data = $ini->read();
        self::assertArrayHasKey('database', $data);
        $data = $ini->read('database');
        self::assertArrayHasKey('hostname', $data);
        $data = $ini->read('database', 'hostname');
        self::assertEquals('zephyrus_database', $data);
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
        self::assertArrayHasKey('test', $data);
        $data = $ini->read('test');
        self::assertArrayHasKey('propertyA', $data);
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
                'testBoolean' => true
            ],
            'test2' => 3
        ]);
        $ini->save();
        $ini = new ConfigurationFile(ROOT_DIR . '/test.ini');
        $data = $ini->read('test', 'propertyA');
        self::assertEquals(1, $data);
        self::assertTrue(is_int($data));
        @unlink(ROOT_DIR . '/test.ini');
    }

//    public function testLock()
//    {
//        $this->expectException(\RuntimeException::class);
//        $ini = new ConfigurationFile('/etc/lock.ini');
//        $ini->write([
//            'test' => 5
//        ]);
//        $ini->save();
//    }
}
