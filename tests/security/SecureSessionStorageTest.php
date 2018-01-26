<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class SecureSessionStorageTest extends TestCase
{
    public function testEncryption()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => true
        ];
        Session::kill();
        Session::getInstance($config)->start();
        $_SESSION['test'] = 'je suis chiffre';
        $content = $this->getSessionFileContent();
        self::assertTrue(strpos($content, 'je suis chiffre') === false);
    }

    public function testNonEncryption()
    {
        $config = [
            'name' => 'bob'
        ];
        Session::kill();
        Session::getInstance($config)->start();
        $_SESSION['test'] = 'je suis chiffre';
        $content = $this->getSessionFileContent();
        self::assertTrue(strpos($content, 'je suis chiffre') !== false);
    }

    public function testFingerprint()
    {
        $config = [
            'name' => 'bob',
            'fingerprint_agent' => true,
            'fingerprint_ip' => true
        ];
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_FINGERPRINT', $_SESSION));
        $storage->destroy();
    }

    public function testIntervalExpiration()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_interval' => 2
        ];
        Session::kill();
        Session::getInstance($config)->start();
        self::assertTrue(key_exists('__HANDLER_SECONDS_BEFORE_REFRESH', $_SESSION));
        Session::getInstance()->destroy();
    }

    public function testRequestExpiration()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_requests' => 3
        ];
        Session::kill();
        Session::getInstance($config)->start();
        self::assertTrue(key_exists('__HANDLER_REQUESTS_BEFORE_REFRESH', $_SESSION));
        Session::getInstance()->destroy();
    }

    public function testProbabilityExpiration()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_probability' => 0.5
        ];
        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        $storage->destroy();
    }

    public function testExpirationCleanup()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_requests' => 3
        ];
        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertTrue(key_exists('__HANDLER_REQUESTS_BEFORE_REFRESH', $_SESSION));
        $storage->destroy();

        $config = [
            'name' => 'bob',
            'encryption_enabled' => false
        ];
        Session::kill();
        $storage = Session::getInstance($config);
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
        Session::kill();
        $storage = Session::getInstance($config);
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
            'refresh_after_requests' => 2
        ];
        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        $oldId = $storage->getId();

        $storage->start();
        $newId = $storage->getId();
        self::assertEquals($newId, $oldId);

        $storage->destroy();
    }

    public function testObsoleteByTimer()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'refresh_after_interval' => 1
        ];
        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        $oldId = session_id();
        sleep(1);

        $storage->start();
        $newId = session_id();
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
        Session::kill();
        Session::getInstance($config)->start();
    }

    public function testDecoy()
    {
        $config = [
            'name' => 'bob',
            'decoys' => 10
        ];
        $_COOKIE = [];
        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertEquals(10, count($_COOKIE));
        $storage->destroy();
    }

    public function testDecoyArray()
    {
        $config = [
            'name' => 'bob',
            'decoys' => ['bob', 'lewis', 'carol']
        ];
        $_COOKIE = [];
        Session::kill();
        $storage = Session::getInstance($config);
        $storage->start();
        self::assertEquals(3, count($_COOKIE));
        self::assertTrue(isset($_COOKIE['bob']));
        $storage->destroy();
    }

    private function getSessionFileContent()
    {
        session_commit();
        return file_get_contents(Session::getSavePath() . '/sess_' . session_id());
    }
}