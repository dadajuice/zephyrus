<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionTest extends TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testNotSecure()
    {
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        $session = Session::getInstance();
        $session->start();
        $session->destroy();
    }

    /**
     * @depends testNotSecure
     */
    public function testHas()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $session = Session::getInstance();
        $session->start();
        $_SESSION['test'] = '1234';
        self::assertEquals(true, $session->has('test'));
        $session->destroy();
    }

    /**
     * @depends testNotSecure
     */
    public function testSet()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $session = Session::getInstance();
        $session->start();
        $session->set('val', '4567');
        self::assertEquals('4567', $_SESSION['val']);
        $session->destroy();
    }

    /**
     * @depends testNotSecure
     */
    public function testRead()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $session = Session::getInstance();
        $session->start();
        $session->set('val', '4567');
        self::assertEquals('4567', $session->read('val'));
        self::assertEquals(null, $session->read('ertytr'));
        $session->destroy();
    }

    /**
     * @depends testNotSecure
     */
    public function testRemove()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $session = Session::getInstance();
        $session->start();
        $session->set('val', '4567');
        self::assertEquals('4567', $_SESSION['val']);
        $session->remove('val');
        self::assertFalse(isset($_SESSION['val']));
        self::assertFalse($session->has('val'));
        $session->destroy();
    }

    /**
     * @depends testNotSecure
     */
    public function testRefresh()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $session = Session::getInstance();
        $session->start();
        $id = session_id();
        $session->refresh();
        self::assertNotEquals($id, session_id());
        $session->destroy();
    }

    /**
     * @depends testNotSecure
     */
    public function testRestart()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $session = Session::getInstance();
        $session->start();
        $_SESSION['test'] = '123';
        $session->restart();
        self::assertFalse(isset($_SESSION['test']));
        $session->destroy();
    }
}