<?php namespace Zephyrus\Tests\Database\Brokers;

use stdClass;
use Zephyrus\Database\Brokers\DatabaseBroker;
use Zephyrus\Database\Core\Database;
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

    public function testSessionVariable()
    {
        $instance = $this->buildBroker();
        $result = $instance->findSessionVariable("zephyrus.test");
        self::assertEquals('Hello World', $result);
        $result = $instance->findSessionVariable("zephyrus.dsfkjsdjkf");
        self::assertNull($result);
    }

    public function testInsert()
    {
        $instance = $this->buildBroker();
        $result = $instance->insert((object) [
            'name' => 'The Destroyer',
            'alter' => 'Bob Lewis',
            'power' => 40
        ]);
        self::assertEquals(7, $result);
        self::assertEquals(7, $instance->getDatabase()->getLastInsertedId('heroes_id_seq'));
        $result = $instance->findById(7);
        self::assertNotNull($result);
        self::assertEquals("Bob Lewis", $result->alter);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        $instance = $this->buildBroker();
        $instance->update(7, (object) [
            'name' => 'The Destroyer2',
            'alter' => 'Bob Lewis2',
            'power' => 42
        ]);
        $result = $instance->findById(7);
        self::assertNotNull($result);
        self::assertEquals("The Destroyer2", $result->name);
        self::assertEquals("Bob Lewis2", $result->alter);
        self::assertEquals(42, $result->power);
    }

    private function buildBroker(): DatabaseBroker
    {
        $database = $this->buildDatabase();
        return new class($database) extends DatabaseBroker {

            public function __construct(?Database $database = null)
            {
                parent::__construct($database);
                $this->addSessionVariable('zephyrus.test', 'Hello World');
            }

            public function findById(int $id): ?stdClass
            {
                return $this->selectSingle("SELECT * FROM heroes WHERE id = ?", [$id]);
            }

            public function findAll(): array
            {
                return $this->select("SELECT * FROM heroes");
            }

            public function insert(stdClass $heroes): int
            {
                return $this->query("INSERT INTO heroes(name, alter, power) VALUES (?, ?, ?) RETURNING id", [
                    $heroes->name, $heroes->alter, $heroes->power
                ])->id;
            }

            public function update(int $heroesId, stdClass $heroes)
            {
                $this->rawQuery("UPDATE heroes SET name = ?, alter = ?, power = ? WHERE id = ?", [
                    $heroes->name, $heroes->alter, $heroes->power, $heroesId
                ]);
            }
        };
    }
}
