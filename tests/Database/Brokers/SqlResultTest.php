<?php namespace Zephyrus\Tests\Database\Brokers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Brokers\SqlResult;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseSource;
use Zephyrus\Exceptions\FatalDatabaseException;

class SqlResultTest extends TestCase
{
    public function testToArray()
    {
        $db = $this->initializeDatabase();
        $statement = $db->query("SELECT * FROM heroes");
        $result = new SqlResult($statement);
        $rows = $result->toArray();
        self::assertEquals("Wonder Woman", $rows[3]->name);
        self::assertEquals("Green Lantern", $rows[4]->name);
    }

    public function testStream()
    {
        $db = $this->initializeDatabase();
        $statement = $db->query("SELECT * FROM heroes");
        $result = new SqlResult($statement);
        $result->stream(function ($row) {
            self::assertEquals("Batman", $row->name);
            return false;
        });
        self::assertTrue(true);
    }

    public function testChunks()
    {
        $db = $this->initializeDatabase();
        $statement = $db->query("SELECT * FROM heroes");
        $result = new SqlResult($statement);
        $result->chunks(function ($rows) {
            self::assertCount(2, $rows);
            self::assertEquals("Batman", $rows[0]->name);
            self::assertEquals("Superman", $rows[1]->name);
            return false;
        }, 2);
        self::assertTrue(true);
    }

    /**
     * Since the database is in memory, it will be destroyed if the instance changes.
     *
     * @return Database
     * @throws FatalDatabaseException
     */
    private function initializeDatabase(): Database
    {
        $db = new Database(new DatabaseSource());
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT NULL);');
        $db->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        $db->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        $db->query("INSERT INTO heroes(id, name) VALUES (3, 'Aquaman');");
        $db->query("INSERT INTO heroes(id, name) VALUES (4, 'Wonder Woman');");
        $db->query("INSERT INTO heroes(id, name) VALUES (5, 'Green Lantern');");
        $db->query("INSERT INTO heroes(id, name) VALUES (6, 'Green Arrow');");
        return $db;
    }
}
