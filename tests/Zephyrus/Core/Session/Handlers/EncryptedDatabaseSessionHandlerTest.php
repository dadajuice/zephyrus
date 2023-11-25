<?php namespace Zephyrus\Tests\Core\Session\Handlers;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;
use Zephyrus\Core\Session;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\DatabaseSession;
use Zephyrus\Security\Cryptography;

class EncryptedDatabaseSessionHandlerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        DatabaseSession::initiate(Configuration::getDatabase());
        $db = DatabaseSession::getInstance()->getDatabase();
        $db->query("DROP TABLE IF EXISTS session");
        $db->query('CREATE TABLE session(session_id TEXT PRIMARY KEY, access INT, data TEXT)');
    }

    public static function tearDownAfterClass(): void
    {
        $db = new Database(Configuration::getDatabase());
        $db->query("DROP TABLE IF EXISTS session");
    }

    public function testReadEncryptedSession()
    {
        $session = $this->buildSession();
        $session->start();
        

        $_SESSION['test'] = '1234';
        $this->assertTrue($session->has('test'));
        $this->assertEquals('1234', $session->get('test'));
        $this->assertEquals('1234', session('test'));
        $this->assertEquals('none', session('kldsfjljdfs', 'none'));
        $sessId = $session->getId();
        $encryptionKey = $_COOKIE['key_' . $session->getName()];
        session_write_close(); // Mimic request end

        $db = new Database(Configuration::getDatabase());
        $statement = $db->query("SELECT * FROM session WHERE session_id = ?", [$sessId]);
        $row = $statement->next();
        $this->assertNotNull($row);
        $this->assertNotEquals('test|s:4:"1234";', $row->data);

        $this->assertNotEmpty($encryptionKey);
        $result = Cryptography::decrypt($row->data, $encryptionKey);
        $this->assertEquals('test|s:4:"1234";', $result);
    }

    #[Depends("testReadEncryptedSession")]
    public function testDatabaseReadability()
    {
        session_unset(); // Remove memory session if any ... make sure it reads from Database
        $session = $this->buildSession();
        $session->start();
        self::assertTrue($session->has('test'));
        self::assertEquals('1234', $session->get('test'));
    }

    #[Depends("testDatabaseReadability")]
    public function testDatabaseDestroy()
    {
        $session = $this->buildSession();
        $session->destroy();
        $db = new Database(Configuration::getDatabase());
        $row = $db->query("SELECT * FROM session")->next();
        $this->assertNull($row);
    }

    #[Depends("testDatabaseDestroy")]
    public function testNormalBehavior()
    {
        DatabaseSession::initiate(Configuration::getDatabase());

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
        $sessId = $session->getId();
        $encryptionKey = $_COOKIE['key_' . $session->getName()];

        session_write_close(); // Mimic request end
        $db = new Database(Configuration::getDatabase());
        $row = $db->query("SELECT * FROM session WHERE session_id = ?", [$sessId])->next();
        $this->assertNotNull($row);
        $this->assertNotEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $row->data);

        $this->assertNotEmpty($encryptionKey);
        $result = Cryptography::decrypt($row->data, $encryptionKey);
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

        $db = new Database(Configuration::getDatabase());
        $row = $db->query("SELECT * FROM session WHERE session_id = ?", [$session->getId()])->next();
        $this->assertNotNull($row);
        $this->assertNotEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $row->data);

        $encryptionKey = $_COOKIE['key_' . $session->getName()];
        $this->assertNotEmpty($encryptionKey);
        $result = Cryptography::decrypt($row->data, $encryptionKey);
        $this->assertEquals('val|s:4:"4567";val2|s:3:"999";val3|s:4:"2345";test|s:4:"allo";', $result);

        $mimicExpiredAccess = strtotime("-2 hours", time()); // Normal session is about 24 minutes
        $db->query("UPDATE session SET access = ? WHERE session_id = ?", [$mimicExpiredAccess, $session->getId()]);
        $this->assertEquals(1, session_gc());
        $row = $db->query("SELECT * FROM session WHERE session_id = ?", [$session->getId()])->next();
        $this->assertNull($row);

        $session->destroy();
    }

    private function buildSession(): Session
    {
        return new Session([
            'storage' => 'database',
            'encrypted' => true
        ]);
    }
}
