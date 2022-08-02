<?php namespace Zephyrus\Tests\Database\Core;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Adapters\Sqlite\SqliteAdapter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseConfiguration;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseTest extends TestCase
{
    public function testFailedConnection()
    {
        self::expectException(FatalDatabaseException::class);
        self::expectExceptionCode(FatalDatabaseException::CONNECTION_FAILED);
        new Database(new DatabaseConfiguration([
            'dbms' => 'pgsql',
            'host' => 'localhost',
            'port' => '',
            'charset' => 'utf8',
            'database' => 'test',
            'username' => 'bob',
            'password' => 'lewis'
        ]));
    }

    public function testLastInsertId()
    {
        $db = $this->initializeDatabase();
        $res = $db->query("INSERT INTO heroes(id, name, enabled, power) VALUES (2, 'Bob Gratton', 1, 1.2);");
        self::assertEquals(1, $res->count());
        self::assertEquals(2, $db->getLastInsertedId());
    }

    public function testMetaQueries()
    {
        $db = $this->initializeDatabase();
        $interrogator = $db->getSchemaInterrogator();
        self::assertEquals(['heroes'], $interrogator->getAllTableNames());
        self::assertEquals(['id', 'name', 'enabled', 'power'], $interrogator->getAllColumnNames('heroes'));
        self::assertEquals([(object) ['column' => 'id', 'type' => 'PRIMARY KEY']], $interrogator->getAllConstraints('heroes'));
        self::assertEquals(4, count($interrogator->getAllColumns('heroes')));
        self::assertEquals(1, count($interrogator->getAllTables()));
    }

    public function testGetSource()
    {
        $db = $this->initializeDatabase();
        $source = $db->getSource();
        self::assertEquals("sqlite", $source->getDatabaseManagementSystem());
        self::assertEquals(":memory:", $source->getDatabaseName());
    }

    public function testGetConnector()
    {
        $db = $this->initializeDatabase();
        $connector = $db->getHandle();
        self::assertInstanceOf(\PDO::class, $connector);
    }

    public function testGetAdapter()
    {
        $db = $this->initializeDatabase();
        $adapter = $db->getAdapter();
        self::assertInstanceOf(SqliteAdapter::class, $adapter);
    }

    public function testEvaluationOfTypes()
    {
        $db = $this->initializeDatabase();
        $db->query("SELECT * FROM heroes WHERE id = ?", [1]);
        $res = $db->query("SELECT * FROM heroes WHERE power > ?", [2.5]);
        $result = $res->next();

        self::assertEquals("Batman", $result->name);
        self::assertEquals(1, $result->id);
        self::assertTrue(is_int($result->id));
        self::assertEquals(5.6, $result->power);
        self::assertTrue(is_double($result->power));
    }

    public function testQueryError()
    {
        self::expectException(DatabaseException::class);
        $db = $this->initializeDatabase();
        $db->query('CREATE TABL heroes(id NUMERIC PRIMARY KEY, name TEXT);');
    }

    public function testQueryParameterError()
    {
        self::expectException(DatabaseException::class);
        $db = $this->initializeDatabase();
        $db->query('CREATE TABLE foes(? NUMERIC PRIMARY KEY, ? TEXT);', ['id']);
    }

    public function testTransaction()
    {
        $db = $this->initializeDatabase();
        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        $db->commit();

        $statement = $db->query('SELECT * FROM heroes');
        $statement->next();
        $res = $statement->next();
        self::assertEquals('Superman', $res->name);
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

    public function testNestedTransaction()
    {
        $db = $this->initializeDatabase();
        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name) VALUES (2, 'Green Arrow');");
        // -------------- NESTED TRANSACTION --------------
        $db->beginTransaction();
        $db->query("INSERT INTO heroes(id, name) VALUES (3, 'Aquaman');");
        $statement = $db->query('SELECT * FROM heroes');
        $statement->next(); // Skip the one already in database
        $resGreenArrow = $statement->next();
        $resAquaman = $statement->next();
        self::assertEquals('Green Arrow', $resGreenArrow->name);
        self::assertEquals('Aquaman', $resAquaman->name);

        // Cancel nested commit
        $db->rollback();
        $statement = $db->query('SELECT * FROM heroes');
        $statement->next(); // Skip the one already in database
        $resGreenArrow = $statement->next();
        self::assertEquals('Green Arrow', $resGreenArrow->name);
    }

    public function testErrorCommit()
    {
        $this->expectException(FatalDatabaseException::class);
        $db = $this->initializeDatabase();
        $db->commit();
    }

    public function testErrorRollback()
    {
        $this->expectException(FatalDatabaseException::class);
        $db = $this->initializeDatabase();
        $db->rollback();
    }

    /**
     * Since the database is in memory, it will be destroyed if the instance changes.
     *
     * @return Database
     * @throws FatalDatabaseException
     */
    private function initializeDatabase(): Database
    {
        $db = new Database(new DatabaseConfiguration());
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT NULL, enabled INTEGER, power REAL);');
        $db->query("INSERT INTO heroes(id, name, enabled, power) VALUES (1, 'Batman', 1, 5.6);");
        return $db;
    }
}