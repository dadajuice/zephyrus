<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\SessionStorage;

class SecureSessionStorageTest extends TestCase
{
    public function testEncryption()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => true
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        self::assertTrue($storage->isEncryptionEnabled());
        $path = SessionStorage::getSavePath();
        $_SESSION['test'] = 'je suis chiffre';
        session_commit();
        $content = file_get_contents($path . '/sess_' . $storage->getId());
        self::assertFalse(strpos($content, 'je suis chiffre'));
    }

    public function testFingerprint()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'fingerprint_agent' => true,
            'fingerprint_ip' => true
        ];
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [], [], [], $server);
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->getFingerprint()->setIpAddressFingerprinted(true);
        $storage->getFingerprint()->setUserAgentFingerprinted(true);
        $storage->start();
        self::assertTrue($storage->getFingerprint()->isIpAddressFingerprinted());
        self::assertTrue($storage->getFingerprint()->isUserAgentFingerprinted());
        $storage->destroy();
    }

    public function testIntervalExpiration()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_interval' => 2
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->getExpiration()->setRefreshAfterInterval(2);
        $storage->start();
        self::assertEquals(2, $storage->getExpiration()->getRefreshAfterInterval());
        self::assertTrue(key_exists('__HANDLER_SECONDS_BEFORE_REFRESH', $_SESSION));
        $storage->destroy();
    }

    public function testRequestExpiration()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_requests' => 3
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->getExpiration()->setRefreshAfterNthRequests(3);
        $storage->start();
        self::assertEquals(3, $storage->getExpiration()->getRefreshAfterNthRequests());
        self::assertTrue(key_exists('__HANDLER_REQUESTS_BEFORE_REFRESH', $_SESSION));
        $storage->destroy();
    }

    public function testProbabilityExpiration()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_probability' => 0.5
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->getExpiration()->setRefreshProbability(0.5);
        $storage->start();
        self::assertEquals(0.5, $storage->getExpiration()->getRefreshProbability());
        $storage->destroy();
    }

    public function testExpirationCleanup()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_requests' => 3,
            'refresh_after_interval' => 2
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_REQUESTS_BEFORE_REFRESH', $_SESSION));
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false
        ];
        $storage = new SessionStorage($config, $req);
        $storage->start();
        self::assertFalse(key_exists('__HANDLER_REQUESTS_BEFORE_REFRESH', $_SESSION));
        $storage->destroy();
    }

    public function testObsoleteByDirectProbability()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_probability' => 1
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        $oldId = $storage->getId();
        $storage->start();
        $newId = $storage->getId();
        self::assertNotEquals($newId, $oldId);
        $storage->destroy();
    }

    public function testObsoleteByRequest()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_requests' => 2,
            'refresh_probability' => 0.0
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        $oldId = $storage->getId();
        $storage->start();
        $newId = $storage->getId();
        self::assertEquals($newId, $oldId);
        $storage->start();
        $newId = $storage->getId();
        self::assertNotEquals($newId, $oldId);
        $storage->destroy();
    }

    public function testObsoleteByTimer()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_interval' => 1
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        $oldId = $storage->getId();
        sleep(1);
        $storage->start();
        $newId = $storage->getId();
        self::assertNotEquals($newId, $oldId);
        $storage->destroy();
    }

    /**
     * @expectedException \RangeException
     */
    public function testInvalidProbability()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_probability' => 3
        ];
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        new SessionStorage($config, $req);
    }

    public function testDecoy()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'decoys' => 10
        ];
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        self::assertEquals(10, count($_COOKIE));
        $storage->destroy();
    }

    public function testDecoyArray()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'decoys' => ['bob', 'lewis', 'carol']
        ];
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $storage = new SessionStorage($config, $req);
        $storage->start();
        self::assertEquals(3, count($_COOKIE));
        self::assertTrue(isset($_COOKIE['bob']));
        $storage->destroy();
    }
}