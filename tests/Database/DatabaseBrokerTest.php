<?php namespace Zephyrus\Tests\Database;

use PHPUnit\Framework\TestCase;
use stdClass;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseConfiguration;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseBrokerTest extends TestCase
{
    public function testSelect()
    {
        $instance = $this->buildBroker();
        $results = $instance->findAll();
        self::assertCount(6, $results);
        self::assertEquals('Batman', $results[0]->name);
        self::assertEquals('Green Arrow', $results[5]->name);
    }

    public function testSelectSingle()
    {
        $instance = $this->buildBroker();
        $result = $instance->findById(3);
        self::assertEquals('Aquaman', $result->name);
        $result = $instance->findById(99);
        self::assertNull($result);
    }

    private function buildBroker(): DatabaseBroker
    {
        $database = $this->initializeDatabase();
        return new class($database) extends DatabaseBroker {

            public function findById(int $id): ?stdClass
            {
                return $this->selectSingle("SELECT * FROM heroes WHERE id = ?", [$id]);
            }

            public function findAll(): array
            {
                return $this->select("SELECT * FROM heroes");
            }
        };
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
