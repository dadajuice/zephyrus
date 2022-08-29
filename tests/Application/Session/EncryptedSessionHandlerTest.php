<?php namespace Zephyrus\Tests\Application\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Application\Session\EncryptedSessionHandler;

class EncryptedSessionHandlerTest extends TestCase
{
    /**
     * @var EncryptedSessionHandler
     */
    private EncryptedSessionHandler $handler;

    /**
     * @var string
     */
    private string $sessionId;

    protected function setUp(): void
    {
        // Destroy any previous encryption key cookie sent
        if (isset($_COOKIE['key_phpsessid'])) {
            unset($_COOKIE['key_phpsessid']);
        }

        // Destroy any reference to the Session to use native PHP session function for those tests
        Session::getInstance()->destroy();
        Session::kill();

        // Assign handler and start session
        $this->handler = new EncryptedSessionHandler();
        session_save_path(sys_get_temp_dir());
        session_set_save_handler($this->handler);
        session_name('phpsessid');
        session_start(); // Will do the first open

        // Prepares the cookies to simulate they have been sent to the client
        //$this->handler->open(sys_get_temp_dir(), 'phpsessid');
        $this->sessionId = session_id();
        $this->setupCookie();
    }

    protected function tearDown(): void
    {
        $this->handler->destroy($this->sessionId);
        session_destroy();
        unset($_COOKIE['key_phpsessid']);
    }

    public function testSuccessfulSessionDecryption()
    {
        self::assertTrue(isset($_COOKIE['key_phpsessid']));
        $previousCookie = $_COOKIE['key_phpsessid'];
        $this->handler->write($this->sessionId, 'my_secret');
        $result = $this->handler->read($this->sessionId);
        self::assertEquals('my_secret', $result);

        // Close and reopen handler stream (equivalent of ending session)
        $this->handler->close();
        $this->handler->open(sys_get_temp_dir(), 'phpsessid');
        $newCookie = $_COOKIE['key_phpsessid'];
        self::assertEquals($previousCookie, $newCookie);
        $result = $this->handler->read($this->sessionId);
        self::assertEquals('my_secret', $result);
    }

    public function testInvalidKey()
    {
        $this->handler->write($this->sessionId, 'my_ultimate_secret');
        $result = $this->handler->read($this->sessionId);
        self::assertEquals('my_ultimate_secret', $result);
        $this->handler->close();
        $_COOKIE['key_phpsessid'] = "wrong"; // simulate invalid key
        $this->handler->open(sys_get_temp_dir(), 'phpsessid');
        $result = $this->handler->read($this->sessionId);
        self::assertFalse($result);
    }

    /**
     * Simulates cookie sending. Since tests are done locally, the cookie is never really "sent", but it is correctly
     * registered within the headers as it would normally do. This method extracts the value of the [key_phpsessid]
     * Set-Cookie header and place it into the $_COOKIE super global just like the normal workflow of request would
     * do.
     */
    private function setupCookie()
    {
        $cookie = getSetCookieHeader("key_phpsessid");
        $cookieParts = explode("; ", $cookie);
        $value = str_replace('Set-Cookie: key_phpsessid=', '', $cookieParts[0]);
        $_COOKIE['key_phpsessid'] = $value;
    }
}