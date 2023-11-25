<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ConfigurationFile;

class ConfigurationFileTest extends TestCase
{
    public function testLoad()
    {
        $file = new ConfigurationFile(ROOT_DIR . '/config.yml');
        $data = $file->read();
        self::assertArrayHasKey('database', $data);

        $data = $file->read('database');
        self::assertArrayHasKey('hostname', $data);

        $data = $file->read('test', 'nope');
        self::assertEquals('nope', $data);
    }

    public function testWrite()
    {
        $file = new ConfigurationFile(ROOT_DIR . '/test.yml');
        $file->write([
            'test' => [
                'property_a' => 1,
                'property_b' => 2
            ],
            'test_2' => 3
        ]);

        $data = $file->read();
        self::assertArrayHasKey('test', $data);

        $data = $file->read('test');
        self::assertArrayHasKey('property_a', $data);

        $data = $file->read('test', 'propertyA');
        self::assertEquals(1, $data['property_a']);
        $file->writeProperty('test_3', [
            'Junji' => 'Ito'
        ]);
        self::assertEquals("Ito", $file->read('test_3')['Junji']);
    }

    public function testSave()
    {
        $file = new ConfigurationFile(ROOT_DIR . '/test.yml');
        $file->write([
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
        $file->save();

        $file = new ConfigurationFile(ROOT_DIR . '/test.yml');
        $data = $file->read('test');
        self::assertEquals([
            'propertyA' => 1,
            'propertyB' => 2,
            'propertyC' => ['b', 'd'],
            'propertyD' => [
                'a' => 'b',
                'b' => 'd'
            ],
            'testBoolean' => true
        ], $data);

        self::assertTrue(is_int($data['propertyB']));
        @unlink(ROOT_DIR . '/test.yml');
    }
}
