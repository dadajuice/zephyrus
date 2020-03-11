<?php namespace Zephyrus\Tests\database;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Broker;
use Zephyrus\Database\Database;
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
        self::$database = new Database('sqlite::memory:');
        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        self::$database->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (3, 'Aquaman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (4, 'Flash');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (5, 'Ratman');");
    }

    public function testWithoutFilter()
    {
        $r = new Request('http://test.local', 'GET');
        RequestFactory::set($r);

        $broker = $this->buildBroker();
        $results = $broker->findAll();

    }

    public function testWithFilter()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
        RequestFactory::set($r);

        $broker = $this->buildBroker();
        $results = $broker->findAll();

    }

    public function testWithInvalidSort()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'birthday']]);
        RequestFactory::set($r);

        $broker = $this->buildBroker();
        $results = $broker->findAll();
    }

    private function buildBroker()
    {
        return new class(self::$database) extends Broker
        {
            public function __construct(?Database $database = null)
            {
                parent::__construct($database);
                $this->setSearchableFields(['name']);
                $this->setSortableFields(['alias' => 'name']);
            }

            public function count()
            {

            }

            public function findAll()
            {
                return $this->select("SELECT * FROM heroes");
            }
        };
    }
}
