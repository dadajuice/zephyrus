<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Database;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        $db = Database::getInstance();
        $db->query('DROP TABLE heroes;');
    }

    public function testLastInsertId()
    {
        $db = Database::getInstance();
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        $db->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        $db = Database::getInstance();
        self::assertEquals(1, $db->getLastInsertedId());
    }

    /**
     * @depends testLastInsertId
     */
    public function testQueryError()
    {
        $db = Database::getInstance();
        try {
            $db->query('CREATE TABL heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        } catch (DatabaseException $e) {
            self::assertEquals('CREATE TABL heroes(id NUMERIC PRIMARY KEY, name TEXT);', $e->getQuery());
        }
    }

    /**
     * @depends testLastInsertId
     */
    public function testQueryParameterError()
    {
        $db = Database::getInstance();
        try {
            $db->query('CREATE TABLE foes(? NUMERIC PRIMARY KEY, ? TEXT);', ['id']);
        } catch (DatabaseException $e) {
            self::assertEquals('CREATE TABLE foes(? NUMERIC PRIMARY KEY, ? TEXT);', $e->getQuery());
        }
    }

    /**
     * @depends testLastInsertId
     */
    public function testTransaction()
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        $db->commit();

        $statement = $db->query('SELECT * FROM heroes');
        $statement->next();
        $res = $statement->next();
        self::assertEquals('Superman', $res['name']);
        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name) VALUES (3, 'Flash');");
        $db->rollback();
        $statement = $db->query('SELECT * FROM heroes');
        $i = 0;
        while ($statement->next()) {
            ++$i;
        }
        self::assertEquals(2, $i);
    }

    /**
     * @depends testLastInsertId
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testErrorCommit()
    {
        $db = Database::getInstance();
        $db->commit();
    }

    /**
     * @depends testLastInsertId
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testErrorRollback()
    {
        $db = Database::getInstance();
        $db->rollback();
    }
}