<?php namespace Zephyrus\Tests\Application\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionEncryptionTest extends TestCase
{
    public function testEncryption()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => true,
            'fingerprint_ua' => false
        ];
        Session::getInstance()->destroy();
        Session::kill();
        Session::getInstance($config)->start();
        $_SESSION['test'] = 'i am a secret';
        $content = $this->getSessionFileContent();
        self::assertNotEmpty($content);
        self::assertTrue(!str_contains($content, 'i am a secret'));
        unlink(Session::getSavePath() . '/sess_' . session_id());
    }

    public function testNonEncryption()
    {
        $config = [
            'name' => 'bob',
            'encryption_enabled' => false,
            'fingerprint_ua' => false
        ];
        Session::getInstance()->destroy();
        Session::kill();
        Session::getInstance($config)->start();
        $_SESSION['test'] = 'i am a secret';
        $content = $this->getSessionFileContent();
        self::assertNotEmpty($content);
        self::assertTrue(str_contains($content, 'i am a secret'));
        unlink(Session::getSavePath() . '/sess_' . session_id());
    }

    private function getSessionFileContent(): string
    {
        session_commit();
        return file_get_contents(Session::getSavePath() . '/sess_' . session_id());
    }
}
