<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
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
        $storage = new SessionStorage($config, $req);
        $storage->start();
        self::assertTrue($storage->isEncryptionEnabled());
        $path = SessionStorage::getSavePath();
        $_SESSION['test'] = 'je suis chiffre';
        $content = file_get_contents($path . '/sess_' . $storage->getId());
        self::assertFalse(strpos($content, 'je suis chiffre'));
        $storage->destroy();
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
        $storage = new SessionStorage($config, $req);
        $storage->start();
        $_SESSION['test'] = '1234';
        $storage->destroy();
    }
}