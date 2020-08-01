<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Security\EncryptedSessionHandler;

class EncryptedSessionHandlerTest extends TestCase
{
    /**
     * @var EncryptedSessionHandler
     */
    private $handler;

    /**
     * @var string
     */
    private $sessionId;

    protected function setUp()
    {
        if (isset($_COOKIE['key_phpsessid'])) {
            unset($_COOKIE['key_phpsessid']);
        }
        Session::kill();
        $this->handler = new EncryptedSessionHandler();
        session_set_save_handler($this->handler);
        session_name('phpsessid');
        session_start();
        $this->handler->open('/tmp', 'phpsessid');
        $this->sessionId = session_id();
        $this->setupCookie();
    }

    protected function tearDown()
    {
        Session::kill();
        $this->handler->destroy($this->sessionId);
        session_destroy();
        unset($_COOKIE['key_phpsessid']);
    }

    public function testSuccessfulSessionDecryption()
    {
        self::assertTrue(isset($_COOKIE['key_phpsessid']));
        $this->handler->write($this->sessionId, 'my_secret');
        $result = $this->handler->read($this->sessionId);
        self::assertEquals('my_secret', $result);

        // Close and reopen handler stream (equivalent of ending session)
        $this->handler->close();
        $this->handler->open('/tmp', 'phpsessid');
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
        $this->handler->open('/tmp', 'phpsessid');
        $result = $this->handler->read($this->sessionId);
        self::assertNull($result);
    }

    /**
     * Simulates cookie sending. Since tests are done locally, the cookie is never really "sent", but it is correctly
     * registered within the headers as it would normally do. This method extracts the value of the [key_phpsessid]
     * Set-Cookie header and place it into the $_COOKIE super global just like the normal workflow of request would
     * do.
     */
    private function setupCookie()
    {
        $headers = xdebug_get_headers();
        $headers = array_reverse($headers);
        $cookie = null;
        foreach ($headers as $header) {
            if (strpos($header, 'Set-Cookie: key_phpsessid') !== false) {
                $cookie = $header;
                $cookie = str_replace('Set-Cookie: key_phpsessid=', '', $cookie);
                $cookie = str_replace('; path=/', '', $cookie);
                break;
            }
        }
        $_COOKIE['key_phpsessid'] = urldecode($cookie);
    }
}