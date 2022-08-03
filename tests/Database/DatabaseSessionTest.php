<?php namespace Zephyrus\Tests\Database;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\DatabaseSession;

class DatabaseSessionTest extends TestCase
{
    public function testGetFailedInstance()
    {
        self::expectException(RuntimeException::class);
        DatabaseSession::getInstance();
    }

    public function testInitiate()
    {
        DatabaseSession::initiate(Configuration::getDatabaseConfiguration());
        $database = DatabaseSession::getInstance()->getDatabase();
        self::assertEquals("demo", $database->getConfiguration()->getUsername());
        self::assertEquals("zephyrus", $database->getConfiguration()->getDatabaseName());
    }

    public function testInitiateNoSearchPath()
    {
        DatabaseSession::initiate(Configuration::getDatabaseConfiguration(), []);
        $database = DatabaseSession::getInstance()->getDatabase();
        self::assertEmpty(DatabaseSession::getInstance()->getSearchPaths());
        self::assertEquals("demo", $database->getConfiguration()->getUsername());
        self::assertEquals("zephyrus", $database->getConfiguration()->getDatabaseName());
    }
}
