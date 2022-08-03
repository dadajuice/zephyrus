<?php namespace Zephyrus\Tests\Database\Brokers;

use stdClass;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Tests\Database\DatabaseTestCase;

class DatabaseBrokerTest extends DatabaseTestCase
{
    public function testSelect()
    {
        $instance = $this->buildBroker();
        $results = $instance->findAll();
        self::assertCount(6, $results);
        self::assertEquals('Batman', $results[0]->name);
        self::assertEquals('Green Lantern', $results[5]->name);
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
        $database = $this->buildDatabase();
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
}
