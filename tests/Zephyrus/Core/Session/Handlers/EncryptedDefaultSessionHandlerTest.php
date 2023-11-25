<?php namespace Zephyrus\Tests\Core\Session\Handlers;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zephyrus\Core\Session;
use Zephyrus\Security\Cryptography;
use Zephyrus\Utilities\FileSystem\File;

class EncryptedDefaultSessionHandlerTest extends TestCase
{
    public function testNativeInteroperability()
    {
        $session = $this->buildSession();
        $session->start();
        

        $_SESSION['test'] = '1234';
        $this->assertTrue($session->has('test'));
        $this->assertEquals('1234', $session->get('test'));
        $this->assertEquals('1234', session('test'));
        $this->assertEquals('none', session('kldsfjljdfs', 'none'));

        $encryptionKey = $_COOKIE['key_' . $session->getName()];
        session_write_close(); // Mimic request end (and session end)

        $file = $session->getSessionFile();
        $this->assertNotNull($file);
        $this->assertNotEquals('test|s:4:"1234";', $file->read());

        $this->assertNotEmpty($encryptionKey);
        $result = Cryptography::decrypt($file->read(), $encryptionKey);
        $this->assertEquals('test|s:4:"1234";', $result);
    }

    #[Depends("testNativeInteroperability")]
    public function testReadability()
    {
        session_unset(); // Remove memory session if any ... make sure it reads from Database
        $session = $this->buildSession();
        $session->start();

        self::assertTrue($session->has('test'));
        self::assertEquals('1234', $session->get('test'));
    }

    #[Depends("testReadability")]
    public function testDestroy()
    {
        $session = $this->buildSession();
        $file = $session->getSessionFile();
        $this->assertNotNull($file);

        $session->destroy();
        $newFile = $session->getSessionFile();
        $this->assertNull($newFile);

        $this->assertFalse(File::exists($file->getPath()));
    }

    #[Depends("testDestroy")]
    public function testNormalBehavior()
    {
        $session = $this->buildSession();
        $session->start();
        

        $this->assertEmpty($_SESSION);
        $this->assertEmpty($session->getAll());

        $session->set('val', '4567');
        $this->assertEquals('4567', $_SESSION['val']);
        $this->assertEquals('4567', session('val'));
        $this->assertEquals('4567', $session->get('val'));

        $session->setAll(['val2' => '8901', 'val3' => '2345']);
        $this->assertEquals('4567', $session->get('val'));
        $this->assertEquals('8901', $_SESSION['val2']);
        $this->assertEquals('8901', session('val2'));
        $this->assertEquals('8901', $session->get('val2'));

        session(['test' => 'allo', 'val2' => '999']);
        $this->assertEquals('allo', $_SESSION['test']);
        $this->assertEquals('999', session('val2'));

        $encryptionKey = $_COOKIE['key_' . $session->getName()];
        session_write_close(); // Mimic request end

        $file = $session->getSessionFile();
        $this->assertNotNull($file);
        $this->assertNotEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $file->read());

        $this->assertNotEmpty($encryptionKey);
        $result = Cryptography::decrypt($file->read(), $encryptionKey);
        $this->assertEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $result);
    }

    #[Depends("testNormalBehavior")]
    public function testGarbageCollector()
    {
        $session = $this->buildSession();
        $session->start();

        $this->assertEquals([
            'val' => "4567",
            'val2' => "999",
            'val3' => "2345",
            'test' => "allo"
        ], $session->getAll());
        $this->assertEquals(0, session_gc());

        $file = $session->getSessionFile();
        $this->assertNotNull($file);
        $this->assertNotEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $file->read());

        $encryptionKey = $_COOKIE['key_' . $session->getName()];
        $this->assertNotEmpty($encryptionKey);
        $result = Cryptography::decrypt($file->read(), $encryptionKey);
        $this->assertEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $result);

        $mimicExpiredAccess = strtotime("-2 hours", time()); // Normal session is about 24 minutes
        $file->touch($mimicExpiredAccess);
        clearstatcache();

        $this->assertEquals(1, session_gc());
        $this->assertFalse(File::exists($file->getPath()));

        $session->destroy();
    }

    private function buildSession(): Session
    {
        return new Session([
            'storage' =>  'file',
            'encrypted' => true
        ]);
    }
}
