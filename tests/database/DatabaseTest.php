<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Database;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseTest extends TestCase
{
    /**
     * @var Database
     */
    private static $database;

    public static function setUpBeforeClass()
    {
        self::$database = new Database('sqlite::memory:');
    }

    public function testLastInsertId()
    {
        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        $res = self::$database->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        self::assertEquals(1, $res->count());
        self::assertEquals(1, self::$database->getLastInsertedId());
    }

    /**
     * @depends testLastInsertId
     */
    public function testQueryError()
    {
        try {
            self::$database->query('CREATE TABL heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        } catch (DatabaseException $e) {
            self::assertEquals('CREATE TABL heroes(id NUMERIC PRIMARY KEY, name TEXT);', $e->getQuery());
        }
    }

    /**
     * @depends testLastInsertId
     */
    public function testQueryParameterError()
    {
        try {
            self::$database->query('CREATE TABLE foes(? NUMERIC PRIMARY KEY, ? TEXT);', ['id']);
        } catch (DatabaseException $e) {
            self::assertEquals('CREATE TABLE foes(? NUMERIC PRIMARY KEY, ? TEXT);', $e->getQuery());
        }
    }

    /**
     * @depends testLastInsertId
     */
    public function testTransaction()
    {
        self::$database->beginTransaction();
        self::$database->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        self::$database->commit();

        $statement = self::$database->query('SELECT * FROM heroes');
        $statement->next();
        $res = $statement->next();
        self::assertEquals('Superman', $res['name']);
        self::$database->beginTransaction();
        self::$database->query("INSERT INTO heroes(id, name) VALUES (3, 'Flash');");
        self::$database->rollback();
        $statement = self::$database->query('SELECT * FROM heroes');
        $i = 0;
        while ($statement->next()) {
            ++$i;
        }
        self::assertEquals(2, $i);
    }

    /**
     * @depends testTransaction
     */
    public function testNestedTransaction()
    {
        self::$database->beginTransaction();
        self::$database->query("INSERT INTO heroes(id, name) VALUES (8, 'Green Arrow');");
        self::$database->beginTransaction();
        self::$database->query("INSERT INTO heroes(id, name) VALUES (9, 'Aquaman');");
        self::$database->commit();

        $statement = self::$database->query('SELECT * FROM heroes');
        $statement->next();
        $statement->next();
        $statement->next();
        $res = $statement->next();
        self::assertEquals('Aquaman', $res['name']);
        self::$database->rollback();
        $statement = self::$database->query('SELECT * FROM heroes');
        $i = 0;
        while ($row = $statement->next()) {
            ++$i;
        }
        self::assertEquals(2, $i);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testErrorCommit()
    {
        $db = new Database('sqlite::memory:');
        $db->commit();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testErrorRollback()
    {
        $db = new Database('sqlite::memory:');
        $db->rollback();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testInvalidDsn()
    {
        new Database(';lsdklfhjk');
    }

    public function testBuildFromConfiguration()
    {
        $db = Database::buildFromConfiguration();
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        $res = $db->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        self::assertEquals(1, $res->count());
        self::assertEquals(1, $db->getLastInsertedId());
    }
}