<?php namespace Zephyrus\Tests\Application\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\SessionException;

class SessionConfigurationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Make sure any previous session initiated in another test class will not interfere
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function tearDown(): void
    {
        // After each test, properly destroy the session
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function testInitialisationFromConfigIni()
    {
        $session = Session::getInstance();
        self::assertTrue($session->isEnabled());
        self::assertFalse($session->isEncryptionEnabled());
        self::assertFalse($session->isIpAddressFingerprinted());
        self::assertFalse($session->isUserAgentFingerprinted());
        self::assertEquals("default", $session->getLifetimeMode());
        self::assertEquals(600, $session->getLifetime());
        self::assertEquals('none', $session->getRefreshMode());
        self::assertEquals(0, $session->getRefreshRate());
        self::assertEquals(Session::DEFAULT_SAVE_PATH, $session->getSavePath());
        self::assertEquals("kViaaDAH3L3cDMABvqtO", $session->getName());
    }

    public function testInitialisationFromDefaults()
    {
        $session = Session::getInstance(Session::DEFAULT_CONFIGURATIONS);
        self::assertTrue($session->isEnabled());
        self::assertTrue($session->isEncryptionEnabled());
        self::assertFalse($session->isIpAddressFingerprinted());
        self::assertTrue($session->isUserAgentFingerprinted());
        self::assertEquals("default", $session->getLifetimeMode());
        self::assertEquals(0, $session->getLifetime());
        self::assertEquals('none', $session->getRefreshMode());
        self::assertEquals(0, $session->getRefreshRate());
        self::assertEquals(Session::DEFAULT_SAVE_PATH, $session->getSavePath());
        self::assertEquals(Session::DEFAULT_SESSION_NAME, $session->getName());
    }

    public function testInitialisationFromSystemSavePath()
    {
        $session = Session::getInstance([
            'save_path' => ''
        ]);
        self::assertNotEquals(Session::DEFAULT_SAVE_PATH, $session->getSavePath());
        self::assertTrue($session->getSavePath() == session_save_path()
            || $session->getSavePath() == sys_get_temp_dir());
    }

    public function testInitialisationLifetime()
    {
        $session = Session::getInstance([
            'lifetime' => '24000', // Should work since its numeric
            'fingerprint_ua' => false
        ]);
        self::assertEquals(24000, $session->getLifetime());
        $session->start();
        self::assertEquals(24000, session_get_cookie_params()['lifetime']);
        self::assertEquals(24000, ini_get("session.gc_maxlifetime"));
    }

    public function testRestart()
    {
        $session = Session::getInstance();
        $session->start();
        $session->set('test', 'i am value');
        self::assertEquals('i am value', $_SESSION['test']);
        $session->restart();
        self::assertFalse($session->has('test'));
        self::assertFalse(isset($_SESSION['test']));
    }

    public function testInitialisationNotEnabled()
    {
        $session = Session::getInstance([
            'enabled' => false,
            'fingerprint_ua' => false
        ]);
        self::assertFalse($session->isEnabled());
        self::assertFalse($session->isStarted());
        $session->start();
        self::assertFalse($session->isStarted());
    }

    public function testInitialisationLifetimeReset()
    {
        $session = Session::getInstance([
            'name' => 'unique_name_for_reset',
            'lifetime' => 24000,
            'lifetime_mode' => 'reset',
            'fingerprint_ua' => false
        ]);
        $session->start();
        self::assertEquals(24000, $session->getLifetime());
        self::assertEquals(24000, session_get_cookie_params()['lifetime']);
        self::assertEquals(24000, ini_get("session.gc_maxlifetime"));
        $header = getSetCookieHeader('unique_name_for_reset');
        $header = str_replace("Set-Cookie: ", "", $header);
        $parts = explode("; ", $header);
        self::assertEquals("Max-Age=24000", $parts[2]);
        list ($name, $expires) = explode('=', $parts[1]);
        $timestamp = strtotime($expires);
        self::assertTrue($timestamp == time() + 24000); // Expires
        $session->destroy();
        Session::kill();
        session_name(Session::DEFAULT_SESSION_NAME);
    }

    public function testInitialisationFromSystemDefaultName()
    {
        $session = Session::getInstance([
            'name' => ''
        ]);
        self::assertEquals("phpsessid", $session->getName());
        self::assertEquals(session_name(), $session->getName());
    }

    public function testInitialisationSessionId()
    {
        $session = Session::getInstance();
        self::assertNull($session->getId());
        $session->start();
        self::assertNotNull($session->getId());
    }

    public function testInitialisationInvalidLifetimeMode()
    {
        self::expectException(SessionException::class);
        self::expectExceptionCode(SessionException::ERROR_INVALID_LIFETIME_MODE);
        Session::getInstance([
            'lifetime_mode' => 'wrong'
        ]);
    }

    public function testInitialisationInvalidRefreshRate()
    {
        self::expectException(SessionException::class);
        self::expectExceptionCode(SessionException::ERROR_INVALID_REFRESH_RARE);
        Session::getInstance([
            'refresh_rate' => 'wrong'
        ]);
    }

    public function testInitialisationInvalidLifetime()
    {
        self::expectException(SessionException::class);
        self::expectExceptionCode(SessionException::ERROR_INVALID_LIFETIME);
        Session::getInstance([
            'lifetime' => 'wrong'
        ]);
    }

    public function testInitialisationInvalidSavePathExists()
    {
        self::expectException(SessionException::class);
        self::expectExceptionCode(SessionException::ERROR_SAVE_PATH_NOT_EXIST);
        Session::getInstance([
            'save_path' => '/lksdjfljsdf'
        ]);
    }

    public function testInitialisationInvalidSavePathWrite()
    {
        self::expectException(SessionException::class);
        self::expectExceptionCode(SessionException::ERROR_SAVE_PATH_NOT_WRITABLE);
        Session::getInstance([
            'save_path' => '/root'
        ]);
    }
}
