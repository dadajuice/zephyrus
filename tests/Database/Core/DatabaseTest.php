<?php namespace Zephyrus\Tests\Database\Core;

use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Exceptions\FatalDatabaseException;
use Zephyrus\Tests\Database\DatabaseTestCase;

class DatabaseTest extends DatabaseTestCase
{
    public function testFailedHostConnection()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::CONNECTION_FAILED);
        new Database([
            'hostname' => 'localhost',
            'port' => '',
            'database' => 'test',
            'username' => 'bob',
            'password' => 'lewis'
        ]);
    }

    public function testFailedCredentialsConnection()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::CONNECTION_FAILED);
        new Database([
            'hostname' => 'zephyrus_database',
            'port' => '',
            'database' => 'zephyrus',
            'username' => 'bob',
            'password' => 'lewis'
        ]);
    }

    public function testFailedDatabaseConnection()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::CONNECTION_FAILED);
        new Database([
            'hostname' => 'zephyrus_database',
            'port' => '',
            'database' => 'test',
            'username' => 'bob',
            'password' => 'lewis'
        ]);
    }

    public function testLastInsertId()
    {
        $db = $this->buildDatabase();
        $res = $db->query("INSERT INTO heroes(id, name, alter, power) VALUES (7, 'Bob Gratton', 'Bob', 0.2);");
        self::assertEquals(1, $res->count());
        //self::assertEquals(7, $db->getLastInsertedId());
    }

    public function testMetaQueries()
    {
        $db = $this->buildDatabase();
        $interrogator = $db->getSchemaInterrogator();
        self::assertEquals(['heroes'], $interrogator->getAllTableNames());
        self::assertEquals(['id', 'name', 'alter', 'power'], $interrogator->getAllColumnNames('heroes'));
        self::assertEquals([(object) ['column' => 'id', 'type' => 'PRIMARY KEY']], $interrogator->getAllConstraints('heroes'));
        self::assertEquals(4, count($interrogator->getAllColumns('heroes')));
        self::assertEquals(1, count($interrogator->getAllTables()));
    }

    public function testGetConfigurations()
    {
        $db = $this->buildDatabase();
        $source = $db->getConfiguration();
        self::assertEquals("demo", $source->getUsername());
        self::assertEquals("zephyrus", $source->getDatabaseName());
    }

    public function testGetHandle()
    {
        $db = $this->buildDatabase();
        $connector = $db->getHandle();
        self::assertInstanceOf(\PDO::class, $connector);
    }

    public function testEvaluationOfTypes()
    {
        $db = $this->buildDatabase();
        $db->query("SELECT * FROM heroes WHERE id = ?", [1]);
        $res = $db->query("SELECT * FROM heroes WHERE power > ?", [2.5]);
        $result = $res->next();

        self::assertEquals("Batman", $result->name);
        self::assertEquals(1, $result->id);
        self::assertTrue(is_int($result->id));
        self::assertEquals(20.56, $result->power);
        self::assertTrue(is_double($result->power));
    }

    public function testQueryDDLError()
    {
        self::expectException(DatabaseException::class);
        $db = $this->buildDatabase();
        $db->query('CREATE TABL heroes2(id SERIAL PRIMARY KEY, name TEXT)');
    }

    public function testQuerySelectError()
    {
        self::expectException(DatabaseException::class);
        $db = $this->buildDatabase();
        $db->query('SELECT * FROM hero');
    }

    public function testQueryParameterError()
    {
        self::expectException(DatabaseException::class);
        $db = $this->buildDatabase();
        $db->query('CREATE TABLE foes(? NUMERIC PRIMARY KEY, ? TEXT);', ['id']);
    }

    public function testTransaction()
    {
        $db = $this->buildDatabase();
        $this->rebootDatabase($db);

        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name, alter, power) VALUES (7, 'Elvis Gratton', 'Bob Gratton', 0.1)");
        $db->commit();

        $statement = $db->query('SELECT * FROM heroes ORDER BY id DESC');
        $res = $statement->next();
        self::assertEquals('Elvis Gratton', $res->name);
        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name, alter, power) VALUES (8, 'Green Arrow', '', 3.2);");
        $db->rollback();
        $statement = $db->query('SELECT * FROM heroes');
        $i = 0;
        $last = null;
        while (($row = $statement->next()) != null) {
            ++$i;
            $last = $row;
        }
        self::assertEquals("Elvis Gratton", $last->name);
        self::assertEquals(7, $i);
    }

//    public function testNestedTransaction()
//    {
//        $db = $this->buildDatabase();
//        $this->rebootDatabase($db);
//
//        $db->beginTransaction();
//        $db->query("INSERT INTO heroes(id, name, alter, power) VALUES (7, 'Green Arrow', 'Ouf', 3.2)");
//        // -------------- NESTED TRANSACTION --------------
//        $db->beginTransaction();
//        $db->query("INSERT INTO heroes(id, name, alter, power) VALUES (8, 'Darksied', 'Evil', 20.5)");
//        $statement = $db->query('SELECT * FROM heroes ORDER BY id DESC');
//        $resDarksied = $statement->next();
//        $resGreenArrow = $statement->next();
//        self::assertEquals('Darksied', $resDarksied->name);
//        self::assertEquals('Green Arrow', $resGreenArrow->name);
//
//        // Cancel nested commit
//        $db->rollback();
//        $statement = $db->query('SELECT * FROM heroes ORDER BY id DESC');
//        $resGreenArrow = $statement->next();
//        self::assertEquals('Green Arrow', $resGreenArrow->name);
//    }

    public function testErrorCommit()
    {
        $this->expectException(FatalDatabaseException::class);
        $db = $this->buildDatabase();
        $db->commit();
    }

    public function testErrorRollback()
    {
        $this->expectException(FatalDatabaseException::class);
        $db = $this->buildDatabase();
        $db->rollback();
    }
}