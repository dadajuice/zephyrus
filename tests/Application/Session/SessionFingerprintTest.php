<?php namespace Zephyrus\Tests\Application\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\SessionException;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class SessionFingerprintTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Make sure any previous session initiated in another test class will not interfere
        Session::getInstance()->destroy();
        Session::kill();
    }

    protected function tearDown(): void
    {
        // Destroy the session after each test
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function testFingerprintUserAgent()
    {
        $config = [
            'name' => 'session_fingerprint_test',
            'fingerprint_ua' => true,
            'fingerprint_ip' => false
        ];

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));

        Session::kill();

        // Define request
        $server['REMOTE_ADDR'] = '192.168.0.34'; // Ip has changed
        $server['HTTP_USER_AGENT'] = 'chrome'; // But UA stays the same so its valid
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $storage = Session::getInstance($config);
        $storage->start();

        // If it reaches here it means the fingerprint is valid
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));
    }

    public function testInvalidFingerprintUserAgent()
    {
        self::expectException(SessionException::class);
        $config = [
            'name' => 'session_fingerprint_test',
            'fingerprint_ua' => true,
            'fingerprint_ip' => false
        ];

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));

        Session::kill();

        // Define request
        $server['REMOTE_ADDR'] = '192.168.0.34'; // Ip changed
        $server['HTTP_USER_AGENT'] = 'firefox'; // UA changed and will be invalid
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        Session::getInstance($config);
        $storage->start();
    }

    public function testFingerprintIpAddress()
    {
        $config = [
            'name' => 'session_fingerprint_test',
            'fingerprint_ua' => false,
            'fingerprint_ip' => true
        ];

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));

        Session::kill();

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1'; // Ip stays the same
        $server['HTTP_USER_AGENT'] = 'firefox'; // UA changed, but its still valid
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $storage = Session::getInstance($config);
        $storage->start();

        // If it reaches here it means the fingerprint is valid
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));
    }

    public function testInvalidFingerprintIpAddress()
    {
        self::expectException(SessionException::class);
        $config = [
            'name' => 'session_fingerprint_test',
            'fingerprint_ua' => false,
            'fingerprint_ip' => true
        ];

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));

        Session::kill();

        // Define request
        $server['REMOTE_ADDR'] = '192.168.0.30'; // Ip changed!
        $server['HTTP_USER_AGENT'] = 'firefox'; // UA changed
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $storage = Session::getInstance($config);
        $storage->start();
    }

    public function testFingerprintBoth()
    {
        $config = [
            'name' => 'session_fingerprint_test',
            'fingerprint_ua' => true,
            'fingerprint_ip' => true
        ];

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));

        Session::kill();

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $storage = Session::getInstance($config);
        $storage->start();

        // If it reaches here it means the fingerprint is valid
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));
    }

    public function testInvalidFingerprintBoth()
    {
        self::expectException(SessionException::class);
        $config = [
            'name' => 'session_fingerprint_test',
            'fingerprint_ua' => true,
            'fingerprint_ip' => true
        ];

        // Define request
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));

        Session::kill();

        // Define request
        $server['REMOTE_ADDR'] = '192.168.0.30';
        $server['HTTP_USER_AGENT'] = 'firefox';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $storage = Session::getInstance($config);
        $storage->start();
    }
}
