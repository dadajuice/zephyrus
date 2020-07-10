<?php namespace Zephyrus\Tests\database;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Database\DatabaseFactory;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class BrokerFilterTest extends TestCase
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
        self::$database->query("INSERT INTO heroes(id, name) VALUES (5, 'Aatman');");
    }

    public function testWithoutFilter()
    {
        $r = new Request('http://test.local', 'GET');
        RequestFactory::set($r);

        $broker = $this->buildBroker();
        $results = $broker->findAll();
        self::assertEquals(5, count($results));
        self::assertEquals("Batman", $results[0]->name);
    }

    public function testWithFilter()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
        RequestFactory::set($r);

        $broker = $this->buildBroker();
        $broker->applyFilter();
        $results = $broker->findAll();
        self::assertEquals(4, count($results));
        self::assertEquals("Aatman", $results[0]->name);
    }

    /*public function testWithInvalidSort()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'birthday']]);
        RequestFactory::set($r);

        $broker = $this->buildBroker();
        $results = $broker->findAll();
    }*/

    private function buildBroker()
    {
        return new class(self::$database) extends DatabaseBroker
        {
            public function __construct(?Database $database = null)
            {
                parent::__construct($database);
                $this->setSearchableFields(['name']);
                $this->setSortableFields(['alias' => 'name']);
            }

            public function count()
            {
                return $this->filteredSelectSingle("SELECT COUNT(*) FROM heroes");
            }

            public function findAll()
            {
                return $this->filteredSelect("SELECT * FROM heroes");
            }
        };
    }
}
