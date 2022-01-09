<?php namespace Zephyrus\Tests\Application\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionDataTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Make sure any previous session initiated in another test class will not interfere
        Session::getInstance()->destroy();
        Session::kill();
    }

    protected function setUp(): void
    {
        Session::getInstance()->start();
    }

    protected function tearDown(): void
    {
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function testNativeInteroperability()
    {
        $_SESSION['test'] = '1234';
        self::assertTrue(Session::getInstance()->has('test'));
        self::assertEquals('1234', Session::getInstance()->read('test'));
        self::assertEquals('1234', session('test'));
    }

    public function testShorthandInteroperability()
    {
        $_SESSION['test'] = '1234';
        self::assertEquals('1234', session('test'));
    }

    public function testSet()
    {
        Session::getInstance()->set('val', '4567');
        self::assertEquals('4567', $_SESSION['val']);
        self::assertEquals('4567', session('val'));
        self::assertEquals('none', session('kldsfjljdfs', 'none'));
    }

    public function testSetAll()
    {
        Session::getInstance()->setAll(['val' => '4567', 'val2' => '123']);
        self::assertEquals('123', $_SESSION['val2']);
        self::assertEquals('123', session('val2'));
        session(['test' => 'allo', 'val2' => '999']);
        self::assertEquals('allo', $_SESSION['test']);
        self::assertEquals('999', session('val2'));
    }

    public function testRead()
    {
        Session::getInstance()->set('val', '4567');
        self::assertEquals('4567', Session::getInstance()->read('val'));
    }

    public function testReadUnregistered()
    {
        Session::getInstance()->set('test', '999');
        self::assertEquals(null, Session::getInstance()->read('username'));
    }

    public function testReadDefaultUnregistered()
    {
        Session::getInstance()->set('test', '999');
        self::assertEquals('admin', Session::getInstance()->read('username', 'admin'));
    }

    public function testRemove()
    {
        Session::getInstance()->set('val', '4567');
        self::assertEquals('4567', $_SESSION['val']);
        Session::getInstance()->remove('val');
        self::assertFalse(isset($_SESSION['val']));
        self::assertFalse(Session::getInstance()->has('val'));
        self::assertEquals(null, Session::getInstance()->read('val'));
    }
}
