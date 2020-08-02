<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionTest extends TestCase
{
    public function testNotSecure()
    {
        $this->expectException(\InvalidArgumentException::class);
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
        self::assertEquals('4567', sess('val'));
        self::assertEquals('none', sess('kldsfjljdfs', 'none'));
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

    public function testDefaultSessionName()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        Session::kill();
        $session = Session::getInstance([]);
        $session->start();
        self::assertEquals(Session::DEFAULT_SESSION_NAME, $session->getName());
        $session->destroy();
    }

    public function testCustomSessionName()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        Session::kill();
        $session = Session::getInstance([
            'name' => 'test'
        ]);
        $session->start();
        self::assertEquals('test', $session->getName());
        $session->destroy();
    }

    public function testLifetime()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        Session::kill();
        $session = Session::getInstance([
            'lifetime' => 43000
        ]);
        $session->start();
        self::assertEquals(43000, session_get_cookie_params()['lifetime']);
        self::assertTrue(ini_get("session.gc_maxlifetime") > 43000);
        $session->destroy();
        // Restore default session lifetime
        session_set_cookie_params(0);
    }
}