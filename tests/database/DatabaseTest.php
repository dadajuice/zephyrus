<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Database\DatabaseFactory;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseTest extends TestCase
{
    /**
     * @var Database
     */
    private static $database;

    public static function setUpBeforeClass()
    {
        self::$database = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
    }

    public function testLastInsertId()
    {
        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT NULL, enabled INTEGER, power REAL);');
        $res = self::$database->query("INSERT INTO heroes(id, name, enabled, power) VALUES (1, 'Batman', 1, 5.6);");
        self::assertEquals(1, $res->count());
        self::assertEquals(1, self::$database->getLastInsertedId());
    }

    public function testMetaQueries()
    {
        self::assertEquals(['heroes'], self::$database->getAllTableNames());
        self::assertEquals(['id', 'name', 'enabled', 'power'], self::$database->getAllColumnNames('heroes'));
        self::assertEquals([(object) ['column' => 'id', 'type' => 'PRIMARY KEY']], self::$database->getAllConstraints('heroes'));
        self::assertEquals(4, count(self::$database->getAllColumns('heroes')));
        self::assertEquals(1, count(self::$database->getAllTables()));
    }

    /**
     * @depends testLastInsertId
     */
    public function testEvaluationOfTypes()
    {
        $broker = new class(self::$database) extends DatabaseBroker
        {
            public function find()
            {
                $this->select("SELECT * FROM heroes WHERE id = ?", [1]);
                $this->select("SELECT * FROM heroes WHERE power > ?", [2.5]);
            }
        };
        $broker->find();
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
        self::assertEquals('Superman', $res->name);
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
        self::assertEquals('Aquaman', $res->name);
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
        $db = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
        $db->commit();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testErrorRollback()
    {
        $db = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
        $db->rollback();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testInvalidDsn()
    {
        DatabaseFactory::buildFromConfigurations(['dbms' => 'lkdslkjsdfjklsdf']);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testUnavailableDbms()
    {
        DatabaseFactory::buildFromConfigurations([
            'dbms' => 'batman',
            'host' => 'localhost',
            'username' => 'bob'
        ]);
    }
}