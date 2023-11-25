<?php namespace Zephyrus\Tests\Core\Session;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zephyrus\Core\Session;
use Zephyrus\Exceptions\Session\SessionFingerprintException;
use Zephyrus\Tests\RequestUtility;

class SessionFingerprintTest extends TestCase
{
    public function testFingerprintUserAgent()
    {
        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session = new Session([
            'fingerprint_ip' => false,
            'fingerprint_ua' => true
        ]);
        $session->setRequest($request);
        $session->start();

        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));

        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome', // UA stays the same so its valid
            'REMOTE_ADDR' => '192.168.0.341' // Ip has changed
        ]);
        $session->setRequest($request);
        $session->start();

        // If it reaches here it means the fingerprint is valid
        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));
        $this->assertTrue($session->getFingerprintManager()->isUserAgentFingerprinted());
        $this->assertFalse($session->getFingerprintManager()->isIpAddressFingerprinted());
        $session->destroy();
    }

    #[Depends("testFingerprintUserAgent")]
    public function testInvalidFingerprintUserAgent()
    {
        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session = new Session([
            'fingerprint_ip' => false,
            'fingerprint_ua' => true
        ]);
        $session->setRequest($request);
        $session->start();

        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));
        $this->assertTrue($session->getFingerprintManager()->isUserAgentFingerprinted());
        $this->assertFalse($session->getFingerprintManager()->isIpAddressFingerprinted());

        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Firefox', // UA changed!
            'REMOTE_ADDR' => '192.168.0.341' // Ip has changed
        ]);
        $session->setRequest($request);


        try {
            $session->start();
            self::assertTrue(false); // Should not reach ...
        } catch (SessionFingerprintException $exception) {
            $this->assertEquals("ZEPHYRUS SESSION: The session fingerprint is invalid and thus the session cannot be started.", $exception->getMessage());
        }
        $session->destroy();
    }

    #[Depends("testInvalidFingerprintUserAgent")]
    public function testFingerprintIpAddress()
    {
        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session = new Session([
            'fingerprint_ip' => true,
            'fingerprint_ua' => false
        ]);
        $session->setRequest($request);
        $session->start();

        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));

        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Firefox',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session->setRequest($request);
        $session->start();

        // If it reaches here it means the fingerprint is valid
        $session->destroy();
        self::assertFalse(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));
    }

    #[Depends("testFingerprintIpAddress")]
    public function testInvalidFingerprintIpAddress()
    {
        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session = new Session([
            'fingerprint_ip' => true,
            'fingerprint_ua' => false
        ]);
        $session->setRequest($request);
        $session->start();

        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));

        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '192.168.0.341' // Ip has changed
        ]);
        $session->setRequest($request);

        try {
            $session->start();
            self::assertTrue(false); // Should not reach ...
        } catch (SessionFingerprintException $exception) {
            $this->assertEquals("ZEPHYRUS SESSION: The session fingerprint is invalid and thus the session cannot be started.", $exception->getMessage());
        }
        $session->destroy();
    }

    public function testFingerprintBoth()
    {
        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session = new Session([
            'fingerprint_ip' => true,
            'fingerprint_ua' => true
        ]);
        $session->setRequest($request);
        $session->start();

        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));
        $this->assertTrue($session->getFingerprintManager()->isUserAgentFingerprinted());
        $this->assertTrue($session->getFingerprintManager()->isIpAddressFingerprinted());

        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session->setRequest($request);
        $session->start();

        // If it reaches here it means the fingerprint is valid
        $session->destroy();
        self::assertFalse(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));
    }

    public function testInvalidFingerprintBoth()
    {
        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Chrome',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $session = new Session([
            'fingerprint_ip' => true,
            'fingerprint_ua' => false
        ]);
        $session->setRequest($request);
        $session->start();

        self::assertTrue(key_exists('__ZF_SESSION_FINGERPRINT', $_SESSION));

        $request = RequestUtility::get("/", [
            'HTTP_USER_AGENT' => 'Firefox',
            'REMOTE_ADDR' => '192.168.0.341' // Ip has changed
        ]);
        $session->setRequest($request);

        try {
            $session->start();
            self::assertTrue(false); // Should not reach ...
        } catch (SessionFingerprintException $exception) {
            $this->assertEquals("ZEPHYRUS SESSION: The session fingerprint is invalid and thus the session cannot be started.", $exception->getMessage());
        }
        $session->destroy();
    }
}
