<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Broker;
use Zephyrus\Database\Database;
use Zephyrus\Database\Listable;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class ListableBrokerTest extends TestCase
{
    /**
     * @var Database
     */
    private static $database;

    public static function setUpBeforeClass()
    {
        self::$database = new Database('sqlite::memory:');
        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        self::$database->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (3, 'Aquaman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (4, 'Flash');");
    }

    public function testFindAll()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        self::assertEquals('man', $class->getFilter()->getSearch());
        self::assertEquals('Aquaman', $class->findAll()[0]->name);
    }

    public function testRemoveFilter()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        self::assertEquals('Aquaman', $class->findAll()[0]->name);
        $class->removeFilter();
        self::assertEquals('Batman', $class->findAll()[0]->name);
    }

    public function testCount()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        self::assertEquals(4, $class->count());
        $class->applyFilter();
        self::assertEquals(3, $class->count());
    }

    private function buildClass()
    {
        return new class(self::$database) extends Broker implements Listable {
            function count(): int
            {
                return $this->filteredSelectSingle("SELECT COUNT(*) as n FROM heroes")->n;
            }

            function findAll(): array
            {
                return $this->filteredSelect("SELECT * FROM heroes");
            }

            function search(): string
            {
                return "(name LIKE :search)";
            }

            function sort(string $order): array
            {
                return [
                    'alias' => 'name'
                ];
            }
        };
    }
}