<?php namespace Zephyrus\Tests\Core\Session;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zephyrus\Core\Session;
use Zephyrus\Exceptions\Session\SessionHttpOnlyCookieException;
use Zephyrus\Exceptions\Session\SessionUseOnlyCookiesException;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        // Reapply default setting for future session initialisation
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
    }

    public function testCookieNotSecure()
    {
        Session::destroy();
        $this->expectException(SessionUseOnlyCookiesException::class);
        $this->expectExceptionMessage("ZEPHYRUS SESSION: Session configurations are not secure. Fixation may be possible because the session identifier is accessible through the GET parameters. Please review your php.ini or local settings for directive session.use_only_cookies.");
        ini_set('session.use_only_cookies', 0);
        new Session();
    }

    public function testCookieNotHttpOnly()
    {
        Session::destroy();
        $this->expectException(SessionHttpOnlyCookieException::class);
        $this->expectExceptionMessage("ZEPHYRUS SESSION: Session configurations are not secure. Session identifier is accessible beyond the HTTP headers. Please review your php.ini or local settings for directive session.cookie_httponly.");
        ini_set('session.cookie_httponly', 0);
        new Session();
    }

    public function testFacade()
    {
        $session = new Session();
        $session->start();
        $session->set('bibi', 'genevieve');

        $this->assertEquals('genevieve', $_SESSION['bibi']);
        $this->assertEquals('genevieve', $session->get('bibi'));

        $this->assertFalse($session->add('bibi', 'test'));
        $this->assertTrue($session->add('toto', 'test'));
        $this->assertEquals('test', $_SESSION['toto']);
        $this->assertEquals('test', $session->get('toto'));

        $session->setAll([
            'firstname' => 'Bob',
            'lastname' => 'Lewis',
            'age' => '30',
            'pin' => '1111'
        ]);
        $this->assertEquals('1111', $session->get('pin'));
        $this->assertEquals('30', $session->get('age'));

        $session->removeAll(['pin', 'age']);
        $this->assertNull($session->get('pin'));
        $this->assertNull($session->get('age'));

        $session->remove('toto');
        $this->assertNull($session->get('toto'));
    }

    #[Depends("testFacade")]
    public function testRegenerate()
    {
        $session = new Session(); // already started
        $oldSessId = $session->getId();
        $this->assertNotNull($oldSessId);

        $newSessId = $session->regenerate();
        $this->assertEquals($newSessId, session_id());
        $this->assertNotEquals($newSessId, $oldSessId);

        $this->assertEquals("Bob", $session->get('firstname'));
        $this->assertEquals("Bob", $_SESSION['firstname']);
    }

    #[Depends("testRegenerate")]
    public function testClear()
    {
        $session = new Session(); // already started
        $session->clear();
        $this->assertEquals([], $session->getAll());
        $this->assertEquals([], $_SESSION);
    }

    #[Depends("testClear")]
    public function testRestart()
    {
        $session = new Session(); // already started
        $sessId = $session->getId();
        $session->set('user_id', '34');
        $session->restart(); // Does destroy ...
        $this->assertNotEquals($sessId, $session->getId()); // Reboots a new session id
        $this->assertEquals([], $session->getAll());
        $this->assertEquals([], $_SESSION);
    }
}
