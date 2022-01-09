<?php namespace Zephyrus\Tests\Application\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionConfigurationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Make sure any previous session initiated in another test class will not interfere
        Session::getInstance()->destroy();
        Session::kill();
    }

    protected function setUp(): void
    {
        // Before each test, properly start a new session instance
        Session::getInstance()->start();
    }

    protected function tearDown(): void
    {
        // After each test, properly destroy the session
        Session::getInstance()->destroy();
        Session::kill();

        // Restore default cookie parameters
        session_set_cookie_params(0);
    }

    public function testRefresh()
    {
        $id = session_id();
        Session::getInstance()->refresh();
        self::assertNotEquals($id, session_id());
    }

    public function testRestart()
    {
        $_SESSION['test'] = '123';
        Session::getInstance()->restart();
        self::assertFalse(isset($_SESSION['test']));
    }

    public function testDefaultSessionName()
    {
        Session::getInstance()->destroy();
        Session::kill();
        $session = Session::getInstance([]);
        $session->start();
        self::assertEquals(Session::DEFAULT_SESSION_NAME, $session->getName());
    }

    public function testCustomSessionName()
    {
        Session::getInstance()->destroy();
        Session::kill();
        $session = Session::getInstance([
            'name' => 'test'
        ]);
        $session->start();
        self::assertEquals('test', $session->getName());
    }

    public function testLifetime()
    {
        Session::getInstance()->destroy();
        Session::kill();
        $session = Session::getInstance([
            'lifetime' => 43000
        ]);
        $session->start();
        self::assertEquals(43000, session_get_cookie_params()['lifetime']);
        self::assertTrue(ini_get("session.gc_maxlifetime") > 43000);
    }
}
