<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Broker;
use Zephyrus\Database\Database;
use Zephyrus\Database\Listable;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class FilterableTest extends TestCase
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
        self::$database->query("INSERT INTO heroes(id, name) VALUES (5, 'Batman');");
    }

    public function testFindAll()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        self::assertEquals('Aquaman', $class->findAll()[0]->name);
    }

    public function testCount()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'flash']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        self::assertEquals(1, $class->count());
        $class->applyFilter();
        self::assertEquals(1, $class->count());
    }

    public function testHaving()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => '']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        self::assertEquals('2', $class->findAllHaving()[0]->n);
    }

    private function buildClass()
    {
        return new class(self::$database) extends Broker implements Listable {
            function count(): int
            {
                return $this->filteredSelectSingle("SELECT COUNT(*) as n FROM heroes GROUP BY name", [], true)->n;
            }

            function findAll(): array
            {
                return $this->filteredSelect("SELECT * FROM heroes GROUP BY name");
            }

            function findAllHaving()
            {
                return $this->filteredSelect("SELECT count(id) n FROM heroes GROUP BY name HAVING n = 2");
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
