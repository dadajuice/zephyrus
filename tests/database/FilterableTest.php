<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Database\DatabaseFactory;
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
        self::$database = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
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
        return new class(self::$database) extends DatabaseBroker implements Listable
        {
            function __construct(?Database $database = null)
            {
                parent::__construct($database);
                $this->setSortableFields(['alias' => 'name']);
                $this->setSearchableFields(['name']);
            }

            function count(): int
            {
                return $this->selectSingle("SELECT COUNT(*) as n FROM heroes GROUP BY name", [], true)->n;
            }

            function findAll(): array
            {
                return $this->select("SELECT * FROM heroes GROUP BY name");
            }

            function findAllHaving()
            {
                return $this->select("SELECT count(id) n FROM heroes GROUP BY name HAVING n = 2");
            }
        };
    }
}
