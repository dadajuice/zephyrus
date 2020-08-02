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

    public static function setUpBeforeClass(): void
    {
        self::$database = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT, epic NUMERIC);');
        self::$database->query("INSERT INTO heroes(id, name, epic) VALUES (1, 'Batman', 10);");
        self::$database->query("INSERT INTO heroes(id, name, epic) VALUES (2, 'Superman', 10);");
        self::$database->query("INSERT INTO heroes(id, name, epic) VALUES (3, 'Aquaman', 6);");
        self::$database->query("INSERT INTO heroes(id, name, epic) VALUES (4, 'Flash', 6);");
        self::$database->query("INSERT INTO heroes(id, name, epic) VALUES (5, 'Batman', 10);");
    }

    public function testFindAllNoSort()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
        RequestFactory::set($r);
        $class = $this->buildClassNoSort();
        $class->applyFilter();
        self::assertEquals('Batman', $class->findAll()[0]->name);
    }

    public function testFindAll()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        $class->getSortableFields();
        self::assertEquals(['alias' => 'name'], $class->getSortableFields());
        self::assertEquals(['name', 'id'], $class->getSearchableFields());
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

    public function testCountRemoveFilter()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
        RequestFactory::set($r);
        $class = $this->buildClassNoSort();
        $class->applyFilter();
        $allFiltered = $class->findAll();
        self::assertEquals(4, count($allFiltered));
        self::assertEquals(4, $class->count());

        $class->removeFilter();
        $allUnfiltered = $class->findAll();
        self::assertEquals(5, count($allUnfiltered));
        self::assertEquals(5, $class->count());
    }

    public function testHaving()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => '']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        self::assertEquals('2', $class->findAllHaving()[0]->n);
    }

    /*
    // Cannot be tested
    public function testHavingWithoutGroupBy()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'SupEr']]);
        RequestFactory::set($r);
        $class = $this->buildClass();
        $class->applyFilter();
        $list = $class->findAllHavingWithoutGroupBy();
        self::assertEquals('Superman', $list[0]->name);
    }
    */
    private function buildClass()
    {
        return new class(self::$database) extends DatabaseBroker implements Listable
        {
            function __construct(?Database $database = null)
            {
                parent::__construct($database);
                $this->setSortableFields(['alias' => 'name']);
                $this->setSearchableFields(['name', 'id']);
            }

            function count(): int
            {
                return $this->filteredSelectSingle("SELECT COUNT(*) as n FROM heroes GROUP BY name")->n;
            }

            function findAll(): array
            {
                return $this->filteredSelect("SELECT * FROM heroes GROUP BY name");
            }

            function findAllHaving()
            {
                return $this->filteredSelect("SELECT count(id) n FROM heroes GROUP BY name HAVING n = 2");
            }

            // Obtain the most powerful heroes (those with the max epic score)
            // Cannot be tested because of DBMS limitation (GROUP BY clause is required before HAVING)
            function findAllHavingWithoutGroupBy()
            {
                return $this->filteredSelect("SELECT name FROM heroes HAVING MAX(epic)");
            }
        };
    }

    private function buildClassNoSort()
    {
        return new class(self::$database) extends DatabaseBroker implements Listable
        {
            function __construct(?Database $database = null)
            {
                parent::__construct($database);
                $this->setSearchableFields(['name']);
            }

            function count(): int
            {
                return $this->filteredSelectSingle("SELECT COUNT(*) as n FROM heroes")->n;
            }

            function findAll(): array
            {
                return $this->filteredSelect("SELECT * FROM heroes");
            }

            function findAllHaving()
            {
                return $this->filteredSelect("SELECT count(id) n FROM heroes GROUP BY name HAVING n = 2");
            }
        };
    }
}
